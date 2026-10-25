<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;
use App\Models\Survey;
use App\Services\WeatherService;

Schedule::call(function () {
    $upcomingVisits = Survey::whereNotNull('scheduled_at')
        ->where('scheduled_at', '>', now())
        ->where('scheduled_at', '<=', now()->addDays(7))
        ->get();

    $weatherService = app(WeatherService::class);
    foreach ($upcomingVisits as $visit) {
        if ($visit->location_lat && $visit->location_lng) {
            $weatherService->getWeatherForDate((float) $visit->location_lat, (float) $visit->location_lng, $visit->scheduled_at);
        }
    }
})->daily();
