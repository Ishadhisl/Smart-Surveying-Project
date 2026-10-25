<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyBoundary;
use App\Models\SurveyFeature;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubdivisionService
{
    /**
     * Perform a manual split of a boundary polygon by 1 or more cut lines (GeoJSON LineStrings).
     *
     * Validates:
     * - Cut lines must intersect/cross the parent boundary polygon.
     * - Lot count must be between 2 and 20.
     * - Sum of lot areas must equal parent area within 0.01% (sum rule).
     * - No lot can be an invalid or multipart polygon.
     * - No lot can be below the minimum lot area (sliver check).
     *
     * Returns array of lot polygons with stable numbering (ordered west-to-east, south-to-north).
     */
    public function manualSplit(SurveyBoundary $boundary, array $cutLinesGeoJson, float $minLotAreaSqm = 0.0): array
    {
        if (empty($cutLinesGeoJson)) {
            throw new InvalidArgumentException('At least one cut line is required.');
        }

        if (count($cutLinesGeoJson) > 10) {
            throw new InvalidArgumentException('Maximum 10 cut lines allowed.');
        }

        // Build MultiLineString or combined geometry of all cut lines
        $lineGeoms = [];
        foreach ($cutLinesGeoJson as $idx => $line) {
            $lineJson = is_string($line) ? $line : json_encode($line);
            $lineGeoms[] = "ST_SetSRID(ST_GeomFromGeoJSON('{$this->escapeSql($lineJson)}'), 4326)";
        }

        $mergedCutsSql = count($lineGeoms) === 1
            ? $lineGeoms[0]
            : "ST_Union(ARRAY[" . implode(',', $lineGeoms) . "])";

        // Validate that cuts actually cross the boundary
        $crossesCheck = DB::selectOne("
            SELECT
                ST_Crosses(b.geom, {$mergedCutsSql}) OR ST_Intersects(b.geom, {$mergedCutsSql}) AS cuts_cross,
                ST_Contains(b.geom, {$mergedCutsSql}) AS cuts_fully_inside
            FROM survey_boundaries b
            WHERE b.id = ?
        ", [$boundary->id]);

        if (!$crossesCheck || !$crossesCheck->cuts_cross || $crossesCheck->cuts_fully_inside) {
            throw new InvalidArgumentException('Cut line does not fully cross the parent boundary.');
        }

        // Perform split using ST_Split
        // ST_Split(geom, line) returns a GeometryCollection. ST_Dump breaks it into Polygons.
        $lotsData = DB::select("
            WITH raw_split AS (
                SELECT (ST_Dump(ST_Split(b.geom, {$mergedCutsSql}))).geom AS lot_geom
                FROM survey_boundaries b
                WHERE b.id = ?
            ),
            parent_info AS (
                SELECT b.area_sqm AS parent_area, b.geom AS parent_geom
                FROM survey_boundaries b
                WHERE b.id = ?
            )
            SELECT
                ST_AsGeoJSON(r.lot_geom) AS geojson,
                ST_Area(r.lot_geom::geography) AS area_sqm,
                ST_Perimeter(r.lot_geom::geography) AS perimeter_m,
                ST_GeometryType(r.lot_geom) AS geom_type,
                ST_IsValid(r.lot_geom) AS is_valid,
                ST_X(ST_Centroid(r.lot_geom)) AS centroid_x,
                ST_Y(ST_Centroid(r.lot_geom)) AS centroid_y
            FROM raw_split r
            ORDER BY centroid_x ASC, centroid_y ASC
        ", [$boundary->id, $boundary->id]);

        $lotCount = count($lotsData);
        if ($lotCount < 2 || $lotCount > 20) {
            throw new InvalidArgumentException("Subdivision must produce between 2 and 20 lots (produced {$lotCount}).");
        }

        // Validate each lot and check area sum
        $sumArea = 0.0;
        $parentArea = (float) $boundary->area_sqm;
        $results = [];

        foreach ($lotsData as $i => $lot) {
            if (!$lot->is_valid) {
                throw new InvalidArgumentException("Generated lot #{" . ($i + 1) . "} is an invalid polygon.");
            }
            if ($lot->geom_type !== 'ST_Polygon') {
                throw new InvalidArgumentException("Generated lot #{" . ($i + 1) . "} is multipart or not a simple polygon ({$lot->geom_type}).");
            }

            $area = (float) $lot->area_sqm;
            $sumArea += $area;

            if ($minLotAreaSqm > 0 && $area < $minLotAreaSqm) {
                throw new InvalidArgumentException(sprintf(
                    "Lot #%d has area %.2f m², which is below the minimum allowed sliver threshold (%.2f m²).",
                    $i + 1, $area, $minLotAreaSqm
                ));
            }

            $results[] = [
                'lot_no'      => $i + 1,
                'area_sqm'    => round($area, 2),
                'perimeter_m' => round((float) $lot->perimeter_m, 2),
                'perches'     => round($area / config('survey.perch_in_sqm', 25.29285264), 3),
                'geojson'     => json_decode($lot->geojson, true),
                'centroid_x'  => (float) $lot->centroid_x,
                'centroid_y'  => (float) $lot->centroid_y,
            ];
        }

        // 0.01% Sum Rule
        $areaDiffPct = abs($sumArea - $parentArea) / $parentArea * 100.0;
        if ($areaDiffPct > 0.01) {
            throw new InvalidArgumentException(sprintf(
                "Sum of lot areas (%.2f m²) differs from parent boundary area (%.2f m²) by %.4f%%, exceeding the 0.01%% tolerance.",
                $sumArea, $parentArea, $areaDiffPct
            ));
        }

        return $results;
    }

    /**
     * Check if a cut line or lot polygon conflicts with any registered survey features/obstacles.
     *
     * A feature conflict occurs if the cut geometry intersects the feature geometry buffered by clearance_m.
     */
    public function checkFeatureConflicts(int $surveyId, string $geometryGeoJson): array
    {
        $escapedGeoJson = $this->escapeSql($geometryGeoJson);

        $conflicts = DB::select("
            SELECT
                f.id,
                f.type,
                f.label,
                f.clearance_m,
                ST_Intersects(
                    ST_SetSRID(ST_GeomFromGeoJSON('{$escapedGeoJson}'), 4326),
                    ST_Buffer(f.geom::geography, COALESCE(f.clearance_m, 0.0))::geometry
                ) AS intersects
            FROM survey_features f
            WHERE f.survey_id = ?
        ", [$surveyId]);

        $activeConflicts = [];
        foreach ($conflicts as $c) {
            if ($c->intersects) {
                $activeConflicts[] = [
                    'feature_id'  => $c->id,
                    'type'        => $c->type,
                    'label'       => $c->label,
                    'clearance_m' => (float) $c->clearance_m,
                ];
            }
        }

        return $activeConflicts;
    }

    /**
     * Compute lot compactness score (Polsby-Popper score: 4 * pi * Area / Perimeter^2).
     * Score is between 0 and 1, with 1 being a perfect circle. Higher is more compact.
     */
    public function calculateCompactness(float $areaSqm, float $perimeterM): float
    {
        if ($perimeterM <= 0) return 0.0;
        return (4.0 * M_PI * $areaSqm) / ($perimeterM * $perimeterM);
    }

    private function escapeSql(string $str): string
    {
        return str_replace("'", "''", $str);
    }
}
