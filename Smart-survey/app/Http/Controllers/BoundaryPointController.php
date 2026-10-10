<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PricingService;

class BoundaryPointController extends Controller
{
    public function store(Request $request, Survey $survey)
    {
        if (!in_array(Auth::user()->role, ['admin', 'surveyor'])) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'points' => 'required|array|min:3',
            'points.*.latitude' => 'required|numeric',
            'points.*.longitude' => 'required|numeric',
        ]);

        $coords = [];
        foreach ($request->points as $point) {
            $coords[] = [(float)$point['longitude'], (float)$point['latitude']];
        }
        
        // Close the ring
        if (count($coords) > 0 && $coords[0] !== end($coords)) {
            $coords[] = $coords[0];
        }

        $geojson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [$coords]
        ]);

        $query = DB::selectOne("
            WITH geom AS (
                SELECT ST_SetSRID(ST_GeomFromGeoJSON(?), 4326) AS poly
            )
            SELECT 
                ST_IsValid(poly) AS is_valid,
                ST_IsValidReason(poly) AS reason,
                ST_Area(poly::geography) AS area_sqm,
                ST_Perimeter(poly::geography) AS perimeter_m
            FROM geom
        ", [$geojson]);

        if (!$query->is_valid) {
            return back()->with('error', 'Invalid polygon: ' . $query->reason);
        }

        $totalPerches = $query->area_sqm / 25.29285264;
        $acres = floor($totalPerches / 160);
        $perches = fmod($totalPerches, 160);
        $vertexCount = count($coords) - 1;

        DB::table('surveys')->where('id', $survey->id)->update([
            'boundary_polygon' => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('$geojson'), 4326)"),
            'computed_area_sqm' => $query->area_sqm,
            'computed_perimeter_m' => $query->perimeter_m,
            'size_acres' => $acres,
            'size_perches' => $perches,
            'boundary_points' => $vertexCount,
        ]);

        if ($survey->payment_status === 'pending') {
            $pricingService = app(PricingService::class);
            $estimatePrice = $pricingService->calculateEstimate([
                'size_acres' => $acres,
                'size_perches' => $perches,
                'boundary_points' => $vertexCount,
                'purpose' => $survey->purpose,
                'lot_count' => $survey->lot_count,
            ]);
            
            DB::table('surveys')->where('id', $survey->id)->update([
                'estimate_price' => $estimatePrice
            ]);
        }

        // Optional: still save the individual boundary points if needed for UI, but the plan only mentions updating the polygon and vertices count.
        // Let's keep updating boundary_points table just in case the UI depends on it.
        $survey->boundaryPoints()->delete();
        $order = 1;
        foreach ($request->points as $point) {
            $survey->boundaryPoints()->create([
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'point_order' => $order++
            ]);
        }

        return back()->with('success', 'Boundary points and area calculated successfully.');
    }

    public function arView(Survey $survey)
    {
        $points = $survey->boundaryPoints;
        return view('client.ar-view', compact('survey', 'points'));
    }
}
