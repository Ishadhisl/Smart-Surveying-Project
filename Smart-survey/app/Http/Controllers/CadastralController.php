<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyBoundary;
use App\Models\SurveyFeature;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CadastralController extends Controller
{
    /**
     * Show the cadastral workspace for a survey.
     */
    public function workspace(Survey $survey)
    {
        $survey->load(['user', 'boundaries.creator', 'features.creator']);

        $boundaries = $survey->boundaries->map(function (SurveyBoundary $b) {
            $geojson = DB::selectOne(
                'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_boundaries WHERE id = ?',
                [$b->id]
            );
            return [
                'id'           => $b->id,
                'kind'         => $b->kind,
                'version_no'   => $b->version_no,
                'area_sqm'     => $b->area_sqm,
                'perimeter_m'  => $b->perimeter_m,
                'vertex_count' => $b->vertex_count,
                'source'       => $b->source,
                'note'         => $b->note,
                'formatted'    => $b->formattedArea(),
                'creator'      => $b->creator->name ?? '—',
                'created_at'   => $b->created_at->format('M d, Y H:i'),
                'geojson'      => $geojson ? json_decode($geojson->geojson, true) : null,
            ];
        });

        $features = $survey->features->map(function (SurveyFeature $f) {
            $geojson = DB::selectOne(
                'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_features WHERE id = ?',
                [$f->id]
            );
            return [
                'id'          => $f->id,
                'type'        => $f->type,
                'label'       => $f->label,
                'clearance_m' => $f->clearance_m,
                'source'      => $f->source,
                'creator'     => $f->creator->name ?? '—',
                'created_at'  => $f->created_at->format('M d, Y H:i'),
                'geojson'     => $geojson ? json_decode($geojson->geojson, true) : null,
            ];
        });

        $featureTypes = SurveyFeature::typeOptions();
        $defaultClearances = config('survey.default_clearances_m', []);

        return view('admin.cadastral', compact(
            'survey', 'boundaries', 'features', 'featureTypes', 'defaultClearances'
        ));
    }

    /**
     * Store a new boundary polygon (drawn, CSV, or GeoJSON).
     */
    public function storeBoundary(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'kind'       => 'required|string|in:client_sketch,surveyor_final',
            'source'     => 'required|string|in:drawn,csv,geojson',
            'note'       => 'nullable|string|max:500',
            'geojson'    => 'required_if:source,drawn,geojson',
            'csv_file'   => 'required_if:source,csv|file|mimes:csv,txt|max:2048',
        ]);

        // Policy: client_sketch allowed anytime; surveyor_final only by admin
        if ($validated['kind'] === 'surveyor_final' && Auth::user()->role !== 'admin') {
            abort(403, 'Only the licensed surveyor may set the final boundary.');
        }

        // Build GeoJSON from source
        if ($validated['source'] === 'csv' && $request->hasFile('csv_file')) {
            $geojson = $this->geojsonFromCsv($request->file('csv_file'));
        } else {
            $geojsonInput = $validated['geojson'];
            $geojson = is_string($geojsonInput) ? $geojsonInput : json_encode($geojsonInput);
        }

        // Validate & compute with PostGIS
        $result = DB::selectOne("
            WITH geom AS (
                SELECT ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) AS poly
            )
            SELECT
                ST_IsValid(poly) AS is_valid,
                ST_IsValidReason(poly) AS reason,
                ST_Area(poly::geography) AS area_sqm,
                ST_Perimeter(poly::geography) AS perimeter_m,
                ST_NPoints(poly) - 1 AS vertex_count
            FROM geom
        ", [$geojson]);

        if (!$result->is_valid) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid polygon: ' . $result->reason,
            ], 422);
        }

        // Determine next version
        $nextVersion = SurveyBoundary::where('survey_id', $survey->id)
            ->where('kind', $validated['kind'])
            ->max('version_no') + 1;

        $escapedGeojson = str_replace("'", "''", $geojson);
        $boundary = SurveyBoundary::create([
            'survey_id'    => $survey->id,
            'kind'         => $validated['kind'],
            'version_no'   => $nextVersion ?: 1,
            'geom'         => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$escapedGeojson}'), 4326)"),
            'area_sqm'     => $result->area_sqm,
            'perimeter_m'  => $result->perimeter_m,
            'vertex_count' => $result->vertex_count,
            'source'       => $validated['source'],
            'note'         => $validated['note'] ?? null,
            'created_by'   => Auth::id(),
        ]);

        // If surveyor_final, also update the survey's master polygon + pricing
        if ($validated['kind'] === 'surveyor_final') {
            $this->syncSurveyPolygon($survey, $geojson, $result);
        }

        $geojsonOut = DB::selectOne(
            'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_boundaries WHERE id = ?',
            [$boundary->id]
        );

        return response()->json([
            'success'  => true,
            'boundary' => [
                'id'           => $boundary->id,
                'kind'         => $boundary->kind,
                'version_no'   => $boundary->version_no,
                'area_sqm'     => $boundary->area_sqm,
                'perimeter_m'  => $boundary->perimeter_m,
                'vertex_count' => $boundary->vertex_count,
                'formatted'    => $boundary->formattedArea(),
                'source'       => $boundary->source,
                'note'         => $boundary->note,
                'creator'      => Auth::user()->name,
                'created_at'   => $boundary->created_at->format('M d, Y H:i'),
                'geojson'      => $geojsonOut ? json_decode($geojsonOut->geojson, true) : null,
            ],
        ]);
    }

    /**
     * Store a new survey feature (point or polygon).
     */
    public function storeFeature(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'type'        => 'required|string|in:' . implode(',', array_keys(SurveyFeature::typeOptions())),
            'label'       => 'nullable|string|max:100',
            'clearance_m' => 'nullable|numeric|min:0|max:100',
            'geojson'     => 'required',
        ]);

        $geojsonInput = $validated['geojson'];
        $geojson = is_string($geojsonInput) ? $geojsonInput : json_encode($geojsonInput);

        // Validate geometry
        $check = DB::selectOne("
            SELECT ST_IsValid(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)) AS is_valid
        ", [$geojson]);

        if (!$check->is_valid) {
            return response()->json(['success' => false, 'error' => 'Invalid geometry.'], 422);
        }

        $clearance = $validated['clearance_m']
            ?? config("survey.default_clearances_m.{$validated['type']}", 0.0);

        $escapedGeojson = str_replace("'", "''", $geojson);
        $feature = SurveyFeature::create([
            'survey_id'    => $survey->id,
            'type'         => $validated['type'],
            'geom'         => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$escapedGeojson}'), 4326)"),
            'label'        => $validated['label'] ?? ucfirst($validated['type']),
            'clearance_m'  => $clearance,
            'source'       => 'surveyor',
            'created_by'   => Auth::id(),
        ]);

        $geojsonOut = DB::selectOne(
            'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_features WHERE id = ?',
            [$feature->id]
        );

        return response()->json([
            'success' => true,
            'feature' => [
                'id'          => $feature->id,
                'type'        => $feature->type,
                'label'       => $feature->label,
                'clearance_m' => $feature->clearance_m,
                'creator'     => Auth::user()->name,
                'created_at'  => $feature->created_at->format('M d, Y H:i'),
                'geojson'     => $geojsonOut ? json_decode($geojsonOut->geojson, true) : null,
            ],
        ]);
    }

    /**
     * Delete a feature.
     */
    public function destroyFeature(Survey $survey, SurveyFeature $feature)
    {
        if ($feature->survey_id !== $survey->id) {
            abort(404);
        }
        $feature->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Export boundary as GeoJSON file download.
     */
    public function exportBoundary(Survey $survey, SurveyBoundary $boundary)
    {
        if ($boundary->survey_id !== $survey->id) {
            abort(404);
        }

        $result = DB::selectOne(
            'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_boundaries WHERE id = ?',
            [$boundary->id]
        );

        $featureCollection = [
            'type' => 'FeatureCollection',
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'survey_id'    => $survey->id,
                        'kind'         => $boundary->kind,
                        'version'      => $boundary->version_no,
                        'area_sqm'     => $boundary->area_sqm,
                        'perimeter_m'  => $boundary->perimeter_m,
                        'vertex_count' => $boundary->vertex_count,
                    ],
                    'geometry' => json_decode($result->geojson, true),
                ],
            ],
        ];

        $filename = "survey-{$survey->id}-{$boundary->kind}-v{$boundary->version_no}.geojson";

        return response()->json($featureCollection)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Type', 'application/geo+json');
    }

    /**
     * Export boundary as CSV (lat,lng rows).
     */
    public function exportBoundaryCsv(Survey $survey, SurveyBoundary $boundary)
    {
        if ($boundary->survey_id !== $survey->id) {
            abort(404);
        }

        $result = DB::selectOne(
            'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_boundaries WHERE id = ?',
            [$boundary->id]
        );

        $geojson = json_decode($result->geojson, true);
        $coords = $geojson['coordinates'][0] ?? [];

        $filename = "survey-{$survey->id}-{$boundary->kind}-v{$boundary->version_no}.csv";

        $callback = function () use ($coords) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['latitude', 'longitude']);
            foreach ($coords as $i => $coord) {
                // Skip closing point (duplicate of first)
                if ($i === count($coords) - 1 && $coords[0] === $coord) continue;
                fputcsv($file, [$coord[1], $coord[0]]); // GeoJSON is [lng,lat]
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /* ==============================
       PRIVATE HELPERS
       ============================== */

    /**
     * Build a GeoJSON polygon string from a CSV file (latitude,longitude columns).
     */
    private function geojsonFromCsv($file): string
    {
        $rows = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_map('strtolower', array_map('trim', array_shift($rows)));

        $latIdx = array_search('latitude', $header);
        $lngIdx = array_search('longitude', $header);

        if ($latIdx === false || $lngIdx === false) {
            abort(422, 'CSV must have "latitude" and "longitude" columns.');
        }

        $coords = [];
        foreach ($rows as $row) {
            if (count($row) <= max($latIdx, $lngIdx)) continue;
            $lat = (float) trim($row[$latIdx]);
            $lng = (float) trim($row[$lngIdx]);
            if ($lat == 0 && $lng == 0) continue;
            $coords[] = [$lng, $lat]; // GeoJSON: [lng, lat]
        }

        if (count($coords) < 3) {
            abort(422, 'CSV must contain at least 3 coordinate rows.');
        }

        // Close the ring
        if ($coords[0] !== end($coords)) {
            $coords[] = $coords[0];
        }

        return json_encode([
            'type'        => 'Polygon',
            'coordinates' => [$coords],
        ]);
    }

    /**
     * Sync the survey's master polygon and pricing from the final boundary.
     */
    private function syncSurveyPolygon(Survey $survey, string $geojson, object $result): void
    {
        $totalPerches = $result->area_sqm / config('survey.perch_in_sqm', 25.29285264);
        $acres = floor($totalPerches / 160);
        $perches = fmod($totalPerches, 160);
        $vertexCount = $result->vertex_count;

        $escapedGeojson = str_replace("'", "''", $geojson);
        $updateData = [
            'boundary_polygon'   => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$escapedGeojson}'), 4326)"),
            'computed_area_sqm'  => $result->area_sqm,
            'computed_perimeter_m' => $result->perimeter_m,
            'size_acres'         => $acres,
            'size_perches'       => $perches,
            'boundary_points'    => $vertexCount,
        ];

        // Only recompute estimate if advance NOT yet paid
        if ($survey->payment_status === 'pending') {
            $pricingService = app(PricingService::class);
            $updateData['estimate_price'] = $pricingService->calculateEstimate([
                'size_acres'      => $acres,
                'size_perches'    => $perches,
                'boundary_points' => $vertexCount,
                'purpose'         => $survey->purpose,
                'lot_count'       => $survey->lot_count,
            ]);
        }

        DB::table('surveys')->where('id', $survey->id)->update($updateData);
    }
}
