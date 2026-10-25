<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Survey & Subdivision Planning Defaults
    |--------------------------------------------------------------------------
    |
    | IMPORTANT: These values are planning defaults for advisory estimations
    | and spatial simulations. They are NOT legal regulations. Final boundary
    | determination and compliance remain the sole responsibility of the
    | licensed surveyor in the field.
    |
    */

    'partition_fee_per_lot' => env('SURVEY_PARTITION_FEE_PER_LOT', 6500.00),

    // Units: 1 perch = 25.29285264 sqm, 1 acre = 160 perches = 4046.8564224 sqm
    'perch_in_sqm' => 25.29285264,
    'acre_in_sqm'  => 4046.8564224,

    'subdivision' => [
        'default_area_tolerance_pct' => 3.0,
        'min_area_tolerance_pct'     => 0.0,
        'max_area_tolerance_pct'     => 10.0,
        'min_lot_area_perches'       => 1.0,     // Planning default: 1 perch minimum
        'min_road_frontage_m'        => 3.0,     // Planning default: ~10ft frontage
        'max_lots'                   => 6,       // Strict upper limit for deterministic <5s bisection
        'sweep_step_deg'             => 10,      // Default angle step
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Clearance Defaults (Buffer distance in meters)
    |--------------------------------------------------------------------------
    |
    | Used for obstacle conflict detection in the subdivision recommender.
    | Surveyor may override clearances per feature in the field workspace.
    |
    */
    'default_clearances_m' => [
        'well'     => 2.0,
        'building' => 1.0,
        'tree'     => 1.0,
        'canal'    => 2.0,
        'stream'   => 2.0,
        'wall'     => 0.5,
        'road'     => 0.0,
        'other'    => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Weather Planner Risk Thresholds (Open-Meteo)
    |--------------------------------------------------------------------------
    |
    | Limits use strict "greater than" comparisons:
    | High:   Thunder code in (95, 96, 99) OR rain > 5.0mm OR wind > 40.0km/h
    | Medium: Rain > 1.0mm OR wind > 30.0km/h
    | Low:    Otherwise (e.g. rain = 1.0mm and wind = 30km/h are Low)
    |
    */
    'weather' => [
        'high_risk_weather_codes' => [95, 96, 99],
        'high_rain_threshold_mm'  => 5.0,
        'high_wind_threshold_kmh' => 40.0,
        'med_rain_threshold_mm'   => 1.0,
        'med_wind_threshold_kmh'  => 30.0,
        'cache_ttl_hours'         => 3,
        'http_timeout_seconds'    => 3,
        'demo_mode'               => env('WEATHER_DEMO', false),
        'gemini_enabled'          => env('GEMINI_ENABLED', false),
    ],
];
