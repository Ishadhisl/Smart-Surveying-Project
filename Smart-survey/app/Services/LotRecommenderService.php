<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\SurveyBoundary;
use App\Models\SurveyFeature;
use Illuminate\Support\Facades\DB;

class LotRecommenderService
{
    /**
     * Generate equal-area subdivision proposals into N lots.
     *
     * Algorithm (Sweep-Line Bisection with Area-Tolerance Nudging):
     * 1. Check parent polygon bounding box and center.
     * 2. For each sweep angle theta (e.g. 0 to 175 degrees with step 10 or 5 deg):
     *    - Project polygon along rotated coordinate axis.
     *    - For each of the N-1 cuts, use bisection to find the cut line offset that partitions the area into target fraction.
     *    - Apply area-tolerance nudging (+/- tolerance% band) to steer the cut line away from conflicting obstacle buffers if needed.
     * 3. Split the polygon with the parallel cut lines.
     * 4. Check if any generated lot is multipart or invalid -> discard candidate.
     * 5. Calculate conflicts against survey_features with clearance_m.
     * 6. Calculate maximum area deviation from the ideal target (A_total / N).
     * 7. Calculate mean lot compactness (Polsby-Popper score).
     * 8. Rank candidates:
     *    - Primary: fewest obstacle conflicts (0 conflicts first)
     *    - Secondary: lowest maximum area deviation percentage
     *    - Tertiary: highest lot compactness
     * 9. Return top 3 proposals with details, lot GeoJSONs, and ranking explanation.
     *    If no 0-conflict proposal exists, return best proposals with conflicts flagged as warnings (HTTP 200).
     */
    public function recommend(
        SurveyBoundary $boundary,
        int $lotCount,
        float $areaTolerancePct = 3.0,
        int $sweepStepDeg = 10
    ): array {
        // Enforce bounds
        $lotCount = max(2, min($lotCount, config('survey.subdivision.max_lots', 6)));
        $sweepStepDeg = max(5, min($sweepStepDeg, 45));
        $areaTolerancePct = max(0.0, min($areaTolerancePct, config('survey.subdivision.max_area_tolerance_pct', 10.0)));

        $parentAreaSqm = (float) $boundary->area_sqm;
        $targetLotAreaSqm = $parentAreaSqm / $lotCount;

        // Fetch parent geometry envelope and center
        $envelope = DB::selectOne("
            SELECT
                ST_XMin(geom) AS min_x,
                ST_XMax(geom) AS max_x,
                ST_YMin(geom) AS min_y,
                ST_YMax(geom) AS max_y,
                ST_X(ST_Centroid(geom)) AS center_x,
                ST_Y(ST_Centroid(geom)) AS center_y,
                ST_Distance(
                    ST_SetSRID(ST_MakePoint(ST_XMin(geom), ST_YMin(geom)), 4326)::geography,
                    ST_SetSRID(ST_MakePoint(ST_XMax(geom), ST_YMax(geom)), 4326)::geography
                ) AS diag_m
            FROM survey_boundaries
            WHERE id = ?
        ", [$boundary->id]);

        $minX = (float) $envelope->min_x;
        $maxX = (float) $envelope->max_x;
        $minY = (float) $envelope->min_y;
        $maxY = (float) $envelope->max_y;
        $centerX = (float) $envelope->center_x;
        $centerY = (float) $envelope->center_y;
        $spanX = $maxX - $minX;
        $spanY = $maxY - $minY;
        $maxSpan = max($spanX, $spanY) * 2.0;

        // Fetch features with their buffered geometries
        $features = DB::select("
            SELECT
                f.id,
                f.type,
                f.label,
                f.clearance_m,
                ST_AsGeoJSON(f.geom) AS geom_json,
                ST_Buffer(f.geom::geography, COALESCE(f.clearance_m, 0.0))::geometry AS buf_geom
            FROM survey_features f
            WHERE f.survey_id = ?
        ", [$boundary->survey_id]);

        $candidates = [];

        // Sweep angles from 0 to 175 degrees
        for ($angleDeg = 0; $angleDeg < 180; $angleDeg += $sweepStepDeg) {
            $candidate = $this->evaluateSweepAngle(
                $boundary,
                $angleDeg,
                $lotCount,
                $targetLotAreaSqm,
                $parentAreaSqm,
                $minX, $maxX, $minY, $maxY,
                $centerX, $centerY,
                $maxSpan,
                $areaTolerancePct,
                $features
            );

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        // Rank candidates
        usort($candidates, function ($a, $b) {
            // 1. Conflicts count ascending (lowest conflicts first)
            if ($a['conflicts_count'] !== $b['conflicts_count']) {
                return $a['conflicts_count'] <=> $b['conflicts_count'];
            }
            // 2. Max area deviation percentage ascending
            if (abs($a['max_area_dev_pct'] - $b['max_area_dev_pct']) > 0.001) {
                return $a['max_area_dev_pct'] <=> $b['max_area_dev_pct'];
            }
            // 3. Compactness descending (more compact first)
            return $b['avg_compactness'] <=> $a['avg_compactness'];
        });

        // Take top 3 unique proposals
        $topProposals = [];
        $seenAngles = [];
        foreach ($candidates as $cand) {
            if (count($topProposals) >= 3) break;
            $topProposals[] = [
                'proposal_no'       => count($topProposals) + 1,
                'sweep_angle_deg'   => $cand['angle_deg'],
                'conflicts_count'   => $cand['conflicts_count'],
                'max_area_dev_pct'  => round($cand['max_area_dev_pct'], 2),
                'conflict_details'  => $cand['conflict_details'],
                'avg_compactness'   => round($cand['avg_compactness'], 3),
                'ranking_reason'    => $this->buildRankingReason($cand),
                'lots'              => $cand['lots'],
            ];
        }

        return [
            'survey_id'                     => $boundary->survey_id,
            'boundary_id'                   => $boundary->id,
            'lot_count'                     => $lotCount,
            'target_lot_area_sqm'           => round($targetLotAreaSqm, 2),
            'area_tolerance_pct'            => $areaTolerancePct,
            'sweep_step_deg'                => $sweepStepDeg,
            'conflict_free_proposals_exist' => !empty($topProposals) && $topProposals[0]['conflicts_count'] === 0,
            'proposals'                     => $topProposals,
        ];
    }

    /**
     * Evaluate a candidate cut orientation for equal-area N lots.
     */
    private function evaluateSweepAngle(
        SurveyBoundary $boundary,
        int $angleDeg,
        int $lotCount,
        float $targetLotAreaSqm,
        float $parentAreaSqm,
        float $minX, float $maxX, float $minY, float $maxY,
        float $centerX, float $centerY,
        float $maxSpan,
        float $areaTolerancePct,
        array $features
    ): ?array {
        $rad = deg2rad($angleDeg);
        // Normal vector along the sweep direction
        $nx = cos($rad);
        $ny = sin($rad);

        // Direction vector of the cut line (perpendicular to normal)
        $dx = -$ny;
        $dy = $nx;

        // Project boundary bounding corners relative to center onto normal vector to find search range [pMin, pMax]
        $relMinX = $minX - $centerX;
        $relMaxX = $maxX - $centerX;
        $relMinY = $minY - $centerY;
        $relMaxY = $maxY - $centerY;

        $corners = [
            $relMinX * $nx + $relMinY * $ny,
            $relMaxX * $nx + $relMinY * $ny,
            $relMinX * $nx + $relMaxY * $ny,
            $relMaxX * $nx + $relMaxY * $ny,
        ];
        $pMin = min($corners);
        $pMax = max($corners);

        $cutLinesGeoJson = [];
        $prevP = $pMin;

        // Find (lotCount - 1) parallel cut positions
        for ($k = 1; $k < $lotCount; $k++) {
            $targetCumulativeArea = $targetLotAreaSqm * $k;
            $bestP = $this->bisectCutPosition(
                $boundary->id,
                $nx, $ny, $dx, $dy,
                $centerX, $centerY,
                $prevP, $pMax,
                $targetCumulativeArea,
                $parentAreaSqm,
                $maxSpan
            );

            if ($bestP === null) {
                return null; // Could not bisect cut line
            }

            // Nudge cut within area tolerance band if it collides with an obstacle
            $bestP = $this->nudgeCutPosition(
                $boundary->id,
                $nx, $ny, $dx, $dy,
                $centerX, $centerY,
                $bestP,
                $pMin, $pMax,
                $targetLotAreaSqm,
                $parentAreaSqm,
                $areaTolerancePct,
                $maxSpan,
                $features
            );

            // Construct LineString GeoJSON for the cut line
            // Center point of line is relative offset + center
            $cx = $centerX + $bestP * $nx;
            $cy = $centerY + $bestP * $ny;
            $lineStart = [$cx - $dx * $maxSpan, $cy - $dy * $maxSpan];
            $lineEnd   = [$cx + $dx * $maxSpan, $cy + $dy * $maxSpan];

            $cutLinesGeoJson[] = [
                'type' => 'LineString',
                'coordinates' => [$lineStart, $lineEnd],
            ];

            $prevP = $bestP;
        }

        // Perform polygon split with all cuts
        $lineSqlArray = [];
        foreach ($cutLinesGeoJson as $cl) {
            $json = str_replace("'", "''", json_encode($cl));
            $lineSqlArray[] = "ST_SetSRID(ST_GeomFromGeoJSON('{$json}'), 4326)";
        }
        $cutsUnionSql = count($lineSqlArray) === 1 ? $lineSqlArray[0] : "ST_Union(ARRAY[" . implode(',', $lineSqlArray) . "])";

        $lotsQuery = DB::select("
            WITH raw_split AS (
                SELECT (ST_Dump(ST_Split(b.geom, {$cutsUnionSql}))).geom AS lot_geom
                FROM survey_boundaries b
                WHERE b.id = ?
            )
            SELECT
                ST_AsGeoJSON(lot_geom) AS geojson,
                ST_Area(lot_geom::geography) AS area_sqm,
                ST_Perimeter(lot_geom::geography) AS perimeter_m,
                ST_GeometryType(lot_geom) AS geom_type,
                ST_IsValid(lot_geom) AS is_valid,
                ST_X(ST_Centroid(lot_geom)) AS centroid_x,
                ST_Y(ST_Centroid(lot_geom)) AS centroid_y
            FROM raw_split
            ORDER BY centroid_x ASC, centroid_y ASC
        ", [$boundary->id]);

        // Discard candidate if lot count doesn't match or any lot is multipart / invalid
        if (count($lotsQuery) !== $lotCount) {
            return null;
        }

        $lots = [];
        $maxAreaDevPct = 0.0;
        $totalCompactness = 0.0;

        foreach ($lotsQuery as $idx => $lq) {
            if (!$lq->is_valid || $lq->geom_type !== 'ST_Polygon') {
                return null; // Multipart or invalid lot discarded per requirements
            }

            $area = (float) $lq->area_sqm;
            $perim = (float) $lq->perimeter_m;
            $devPct = abs($area - $targetLotAreaSqm) / $targetLotAreaSqm * 100.0;
            if ($devPct > $maxAreaDevPct) {
                $maxAreaDevPct = $devPct;
            }

            $compactness = ($perim > 0) ? (4.0 * M_PI * $area) / ($perim * $perim) : 0.0;
            $totalCompactness += $compactness;

            $lots[] = [
                'lot_no'      => $idx + 1,
                'area_sqm'    => round($area, 2),
                'perches'     => round($area / config('survey.perch_in_sqm', 25.29285264), 3),
                'perimeter_m' => round($perim, 2),
                'compactness' => round($compactness, 3),
                'geojson'     => json_decode($lq->geojson, true),
            ];
        }

        // Check conflicts against features
        $conflictsCount = 0;
        $conflictDetails = [];

        if (!empty($features)) {
            $featureCheck = DB::select("
                SELECT
                    f.id,
                    f.type,
                    f.label,
                    f.clearance_m,
                    ST_Intersects(
                        {$cutsUnionSql},
                        ST_Buffer(f.geom::geography, COALESCE(f.clearance_m, 0.0))::geometry
                    ) AS has_conflict
                FROM survey_features f
                WHERE f.survey_id = ?
            ", [$boundary->survey_id]);

            foreach ($featureCheck as $fc) {
                if ($fc->has_conflict) {
                    $conflictsCount++;
                    $conflictDetails[] = [
                        'feature_id'  => $fc->id,
                        'type'        => $fc->type,
                        'label'       => $fc->label,
                        'clearance_m' => (float) $fc->clearance_m,
                    ];
                }
            }
        }

        return [
            'angle_deg'        => $angleDeg,
            'conflicts_count'  => $conflictsCount,
            'conflict_details' => $conflictDetails,
            'max_area_dev_pct' => $maxAreaDevPct,
            'avg_compactness'  => $totalCompactness / $lotCount,
            'lots'             => $lots,
        ];
    }

    /**
     * Find cut line position along the normal vector that yields target cumulative area via bisection.
     */
    private function bisectCutPosition(
        int $boundaryId,
        float $nx, float $ny, float $dx, float $dy,
        float $centerX, float $centerY,
        float $pLow, float $pHigh,
        float $targetCumulativeArea,
        float $parentAreaSqm,
        float $maxSpan,
        int $maxIter = 12
    ): ?float {
        $low = $pLow;
        $high = $pHigh;
        $bestP = ($low + $high) / 2.0;

        for ($iter = 0; $iter < $maxIter; $iter++) {
            $mid = ($low + $high) / 2.0;

            // Half-plane test polygon
            // Construct a large half-plane polygon on the negative side of the cut line
            $area = $this->measureHalfPlaneArea($boundaryId, $nx, $ny, $dx, $dy, $centerX, $centerY, $mid, $maxSpan);

            if ($area === null) {
                return null;
            }

            if ($area < $targetCumulativeArea) {
                $low = $mid;
            } else {
                $high = $mid;
            }
            $bestP = $mid;
        }

        return $bestP;
    }

    /**
     * Measure the area of the polygon partitioned on the left of the cut line.
     */
    private function measureHalfPlaneArea(
        int $boundaryId,
        float $nx, float $ny, float $dx, float $dy,
        float $centerX, float $centerY,
        float $p,
        float $maxSpan
    ): ?float {
        // Cut line endpoints relative to center
        $cx = $centerX + $p * $nx;
        $cy = $centerY + $p * $ny;
        $p1x = $cx - $dx * $maxSpan;
        $p1y = $cy - $dy * $maxSpan;
        $p2x = $cx + $dx * $maxSpan;
        $p2y = $cy + $dy * $maxSpan;

        // Big box on the negative normal side
        $p3x = $p2x - $nx * $maxSpan * 2;
        $p3y = $p2y - $ny * $maxSpan * 2;
        $p4x = $p1x - $nx * $maxSpan * 2;
        $p4y = $p1y - $ny * $maxSpan * 2;

        $boxGeoJson = str_replace("'", "''", json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [$p1x, $p1y],
                [$p2x, $p2y],
                [$p3x, $p3y],
                [$p4x, $p4y],
                [$p1x, $p1y],
            ]],
        ]));

        $res = DB::selectOne("
            SELECT ST_Area(ST_Intersection(
                b.geom,
                ST_SetSRID(ST_GeomFromGeoJSON('{$boxGeoJson}'), 4326)
            )::geography) AS area
            FROM survey_boundaries b
            WHERE b.id = ?
        ", [$boundaryId]);

        return $res ? (float) $res->area : null;
    }

    /**
     * Nudge cut position within the allowed area tolerance band to avoid obstacle clearance buffers.
     */
    private function nudgeCutPosition(
        int $boundaryId,
        float $nx, float $ny, float $dx, float $dy,
        float $centerX, float $centerY,
        float $bestP,
        float $pMin, float $pMax,
        float $targetLotAreaSqm,
        float $parentAreaSqm,
        float $tolerancePct,
        float $maxSpan,
        array $features
    ): float {
        if ($tolerancePct <= 0 || empty($features)) {
            return $bestP;
        }

        // Check if unnudged cut has conflicts
        $cx = $centerX + $bestP * $nx;
        $cy = $centerY + $bestP * $ny;
        $cutJson = str_replace("'", "''", json_encode([
            'type' => 'LineString',
            'coordinates' => [
                [$cx - $dx * $maxSpan, $cy - $dy * $maxSpan],
                [$cx + $dx * $maxSpan, $cy + $dy * $maxSpan],
            ],
        ]));

        $hasConflict = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM survey_features f
                WHERE f.survey_id = (SELECT survey_id FROM survey_boundaries WHERE id = ?)
                  AND ST_Intersects(
                      ST_SetSRID(ST_GeomFromGeoJSON('{$cutJson}'), 4326),
                      ST_Buffer(f.geom::geography, COALESCE(f.clearance_m, 0.0))::geometry
                  )
            ) AS has_conf
        ", [$boundaryId]);

        if (!$hasConflict || !$hasConflict->has_conf) {
            return $bestP; // Zero conflicts, no nudge needed
        }

        // Test slight offsets in +/- direction within tolerance band
        $pRange = ($pMax - $pMin) * ($tolerancePct / 100.0);
        $steps = [-1.0, 1.0, -0.5, 0.5, -0.25, 0.25];

        foreach ($steps as $s) {
            $candidateP = $bestP + ($s * $pRange);
            $candCx = $centerX + $candidateP * $nx;
            $candCy = $centerY + $candidateP * $ny;
            $candCutJson = str_replace("'", "''", json_encode([
                'type' => 'LineString',
                'coordinates' => [
                    [$candCx - $dx * $maxSpan, $candCy - $dy * $maxSpan],
                    [$candCx + $dx * $maxSpan, $candCy + $dy * $maxSpan],
                ],
            ]));

            $testConflict = DB::selectOne("
                SELECT EXISTS (
                    SELECT 1 FROM survey_features f
                    WHERE f.survey_id = (SELECT survey_id FROM survey_boundaries WHERE id = ?)
                      AND ST_Intersects(
                          ST_SetSRID(ST_GeomFromGeoJSON('{$candCutJson}'), 4326),
                          ST_Buffer(f.geom::geography, COALESCE(f.clearance_m, 0.0))::geometry
                      )
                ) AS has_conf
            ", [$boundaryId]);

            if ($testConflict && !$testConflict->has_conf) {
                return $candidateP; // Found conflict-free offset within tolerance band!
            }
        }

        return $bestP;
    }

    private function buildRankingReason(array $cand): string
    {
        if ($cand['conflicts_count'] === 0) {
            if ($cand['max_area_dev_pct'] <= 0.05) {
                return 'Optimal zero-conflict division with perfectly balanced lot areas.';
            }
            return sprintf(
                'Zero obstacle conflicts with %.1f%% maximum area tolerance adaptation.',
                $cand['max_area_dev_pct']
            );
        }

        return sprintf(
            'Best-effort candidate: %d obstacle conflict(s) flagged as advisory warnings.',
            $cand['conflicts_count']
        );
    }
}
