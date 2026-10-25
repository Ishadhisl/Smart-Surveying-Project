<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyBoundary;
use App\Models\SurveyLot;
use App\Services\LotRecommenderService;
use App\Services\PricingService;
use App\Services\SubdivisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SubdivisionController extends Controller
{
    /**
     * Show the subdivision studio workspace.
     */
    public function workspace(Survey $survey)
    {
        $survey->load(['user', 'boundaries.creator', 'features.creator', 'lots']);

        // Only allow surveyor to access subdivision tool
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Only the licensed surveyor can access the subdivision workspace.');
        }

        // Active boundary: prefer latest surveyor_final, fallback to latest client_sketch
        $boundary = $survey->latestBoundary('surveyor_final') ?? $survey->latestBoundary('client_sketch');

        $boundaryGeojson = null;
        if ($boundary) {
            $raw = DB::selectOne(
                'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_boundaries WHERE id = ?',
                [$boundary->id]
            );
            $boundaryGeojson = $raw ? json_decode($raw->geojson, true) : null;
        }

        // Existing finalized lots if any
        $existingLots = $survey->lots()->orderBy('lot_no')->get()->map(function (SurveyLot $lot) {
            $raw = DB::selectOne(
                'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_lots WHERE id = ?',
                [$lot->id]
            );
            return [
                'id'          => $lot->id,
                'lot_no'      => $lot->lot_no,
                'type'        => $lot->type,
                'area_sqm'    => $lot->area_sqm,
                'perches'     => round($lot->areaInPerches(), 3),
                'formatted'   => $lot->formattedArea(),
                'status'      => $lot->status,
                'geojson'     => $raw ? json_decode($raw->geojson, true) : null,
            ];
        });

        // Features as GeoJSON
        $features = $survey->features->map(function ($f) {
            $raw = DB::selectOne(
                'SELECT ST_AsGeoJSON(geom) AS geojson FROM survey_features WHERE id = ?',
                [$f->id]
            );
            return [
                'id'          => $f->id,
                'type'        => $f->type,
                'label'       => $f->label,
                'clearance_m' => $f->clearance_m,
                'geojson'     => $raw ? json_decode($raw->geojson, true) : null,
            ];
        });

        $planningDefaults = config('survey.subdivision');

        return view('admin.subdivision', compact(
            'survey',
            'boundary',
            'boundaryGeojson',
            'existingLots',
            'features',
            'planningDefaults'
        ));
    }

    /**
     * Compute manual cut split.
     */
    public function manualSplit(Request $request, Survey $survey, SubdivisionService $subdivisionService)
    {
        $validated = $request->validate([
            'boundary_id' => 'required|exists:survey_boundaries,id',
            'cut_lines'   => 'required|array|min:1|max:10',
        ]);

        $boundary = SurveyBoundary::where('id', $validated['boundary_id'])
            ->where('survey_id', $survey->id)
            ->firstOrFail();

        $minLotAreaSqm = (float) config('survey.subdivision.min_lot_area_perches', 1.0)
            * config('survey.perch_in_sqm', 25.29285264);

        try {
            $lots = $subdivisionService->manualSplit(
                $boundary,
                $validated['cut_lines'],
                $minLotAreaSqm
            );

            return response()->json([
                'success' => true,
                'lots'    => $lots,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Recommend equal-area subdivision proposals.
     */
    public function recommend(Request $request, Survey $survey, LotRecommenderService $recommenderService)
    {
        $validated = $request->validate([
            'boundary_id'        => 'required|exists:survey_boundaries,id',
            'lot_count'          => 'required|integer|min:2|max:6',
            'area_tolerance_pct' => 'nullable|numeric|min:0|max:10',
            'sweep_step_deg'     => 'nullable|integer|min:5|max:45',
        ]);

        $boundary = SurveyBoundary::where('id', $validated['boundary_id'])
            ->where('survey_id', $survey->id)
            ->firstOrFail();

        $tolerance = $validated['area_tolerance_pct'] ?? config('survey.subdivision.default_area_tolerance_pct', 3.0);
        $step = $validated['sweep_step_deg'] ?? config('survey.subdivision.sweep_step_deg', 10);

        $result = $recommenderService->recommend(
            $boundary,
            $validated['lot_count'],
            (float) $tolerance,
            (int) $step
        );

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Save finalized lots to the survey_lots table.
     */
    public function finalize(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'boundary_id' => 'required|exists:survey_boundaries,id',
            'lots'        => 'required|array|min:2|max:20',
            'lots.*.lot_no'   => 'required|integer',
            'lots.*.area_sqm' => 'required|numeric|min:0.1',
            'lots.*.type'     => 'nullable|string|in:residential,access_road,reserve',
            'lots.*.geojson'  => 'required',
        ]);

        $boundary = SurveyBoundary::where('id', $validated['boundary_id'])
            ->where('survey_id', $survey->id)
            ->firstOrFail();

        $proposalNo = (SurveyLot::where('survey_id', $survey->id)
            ->where('boundary_id', $boundary->id)
            ->max('proposal_no') ?? 0) + 1;

        DB::transaction(function () use ($survey, $boundary, $proposalNo, $validated) {
            // Delete previously finalized lots for this survey if re-finalizing
            SurveyLot::where('survey_id', $survey->id)->delete();

            foreach ($validated['lots'] as $lotItem) {
                $geomJson = is_string($lotItem['geojson'])
                    ? $lotItem['geojson']
                    : json_encode($lotItem['geojson']);
                $escaped = str_replace("'", "''", $geomJson);

                SurveyLot::create([
                    'survey_id'   => $survey->id,
                    'boundary_id' => $boundary->id,
                    'proposal_no' => $proposalNo,
                    'lot_no'      => $lotItem['lot_no'],
                    'geom'        => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$escaped}'), 4326)"),
                    'type'        => $lotItem['type'] ?? 'residential',
                    'area_sqm'    => $lotItem['area_sqm'],
                    'status'      => 'final',
                    'created_by'  => Auth::id(),
                ]);
            }

            // Update survey lot_count and recalculate estimate ONLY if advance is NOT yet paid
            $newLotCount = count($validated['lots']);
            $survey->lot_count = $newLotCount;

            if ($survey->payment_status === 'pending') {
                $pricingService = app(PricingService::class);
                $survey->estimate_price = $pricingService->calculateEstimate([
                    'size_acres'      => $survey->size_acres,
                    'size_perches'    => $survey->size_perches,
                    'boundary_points' => $survey->boundary_points,
                    'purpose'         => $survey->purpose,
                    'lot_count'       => $newLotCount,
                ]);
            }

            $survey->save();
        });

        return response()->json([
            'success'   => true,
            'message'   => 'Subdivision lots finalized successfully.',
            'lot_count' => count($validated['lots']),
        ]);
    }
}
