<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    private WeatherRiskScorer $scorer;

    public function __construct(WeatherRiskScorer $scorer)
    {
        $this->scorer = $scorer;
    }

    /**
     * Get a 7-day daily forecast with deterministic risk evaluations and ranked date recommendations.
     *
     * @param float $lat
     * @param float $lon
     * @return array Structured forecast with per_day, best_days_in_order, worst_days, top_recommendation
     */
    public function getDailyForecast(float $lat, float $lon): array
    {
        // 1. Check Demo Mode (for offline viva demo / tests)
        if (config('survey.weather.demo_mode', false)) {
            $fixturePath = base_path('tests/Fixtures/weather_fixture.json');
            if (file_exists($fixturePath)) {
                $fixture = json_decode(file_get_contents($fixturePath), true);
                if (isset($fixture['api_response']['daily'])) {
                    $evaluated = $this->scorer->evaluateForecast($fixture['api_response']['daily']);
                    $evaluated['is_demo'] = true;
                    $evaluated['attribution'] = 'Weather data supplied by Open-Meteo.com (CC BY 4.0 offline demo)';
                    return $evaluated;
                }
            }
        }

        // 2. Cache key uses coordinates rounded to 2 decimal places
        $roundedLat = round($lat, 2);
        $roundedLon = round($lon, 2);
        $cacheKey = "weather_forecast_{$roundedLat}_{$roundedLon}";
        $ttlSeconds = (int) config('survey.weather.cache_ttl_hours', 3) * 3600;

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($lat, $lon) {
            try {
                $timeout = (int) config('survey.weather.http_timeout_seconds', 3);
                $response = Http::timeout($timeout)->get("https://api.open-meteo.com/v1/forecast", [
                    'latitude'      => $lat,
                    'longitude'     => $lon,
                    'daily'         => 'precipitation_sum,wind_speed_10m_max,weather_code,cloud_cover_mean,temperature_2m_max',
                    'timezone'      => 'Asia/Colombo',
                    'forecast_days' => 7,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['daily']) && is_array($data['daily'])) {
                        $evaluated = $this->scorer->evaluateForecast($data['daily']);
                        $evaluated['is_demo'] = false;
                        $evaluated['attribution'] = 'Weather forecast provided by Open-Meteo.com (CC BY 4.0)';
                        return $evaluated;
                    }
                }
            } catch (\Exception $e) {
                // Fail gracefully: Cache sentinel instead of null
                return ['error' => true, 'message' => 'Weather service temporarily unreachable'];
            }

            return ['error' => true, 'message' => 'Failed to retrieve forecast'];
        });
    }

    /**
     * Get weather assessment for a specific scheduled visit date.
     * Enforces the 7-day forecast horizon. Dates beyond 7 days return 'unavailable', NEVER current weather.
     *
     * @param float $lat
     * @param float $lon
     * @param mixed $scheduled_at Carbon instance or date string
     * @return array
     */
    public function getWeatherForDate(float $lat, float $lon, $scheduled_at): array
    {
        if (!$scheduled_at) {
            return ['status' => 'unavailable', 'message' => 'No schedule date provided'];
        }

        $target = Carbon::parse($scheduled_at);

        // Horizon 7 days: compare dates with gt()
        if ($target->gt(now()->addDays(7)->endOfDay())) {
            return [
                'status'  => 'unavailable',
                'message' => 'Forecast is only available up to 7 days in advance. Advisory planning defaults apply.',
                'date'    => $target->format('Y-m-d'),
            ];
        }

        $forecast = $this->getDailyForecast($lat, $lon);

        if (!empty($forecast['error']) || empty($forecast['per_day'])) {
            return [
                'status'  => 'error',
                'message' => 'Weather service unavailable',
                'date'    => $target->format('Y-m-d'),
            ];
        }

        $targetDateStr = $target->format('Y-m-d');
        foreach ($forecast['per_day'] as $day) {
            if ($day['date'] === $targetDateStr) {
                return [
                    'status'              => 'available',
                    'date'                => $day['date'],
                    'weekday'             => $day['weekday'],
                    'risk'                => $day['risk'],
                    'score'               => $day['score'],
                    'reasons'             => $day['reasons'],
                    'temp'                => $day['temperature_2m_max'],
                    'precipitation_sum'   => $day['precipitation_sum'],
                    'wind_speed_10m_max'  => $day['wind_speed_10m_max'],
                    'description'         => $this->getWeatherDescription($day['weather_code']),
                    'icon'                => $this->getWeatherIcon($day['weather_code']),
                    'is_bad'              => $day['risk'] === 'High',
                    'attribution'         => $forecast['attribution'] ?? 'Open-Meteo.com (CC BY 4.0)',
                ];
            }
        }

        return [
            'status'  => 'unavailable',
            'message' => 'Date is outside the current 7-day forecast window',
            'date'    => $targetDateStr,
        ];
    }

    /**
     * Legacy adapter for existing views.
     */
    public function getWeather($lat, $lon, $scheduled_at = null)
    {
        if ($scheduled_at) {
            $forDate = $this->getWeatherForDate($lat, $lon, $scheduled_at);
            if ($forDate['status'] === 'available') {
                return [
                    'temp'        => $forDate['temp'],
                    'description' => $forDate['description'],
                    'icon'        => $forDate['icon'],
                    'is_bad'      => $forDate['is_bad'],
                    'risk'        => $forDate['risk'],
                ];
            }
            if ($forDate['status'] === 'unavailable') {
                return ['unavailable' => true, 'message' => $forDate['message']];
            }
            return ['error' => true];
        }

        // Current weather for un-scheduled survey requests
        $roundedLat = round($lat, 2);
        $roundedLon = round($lon, 2);
        $cacheKey = "weather_current_{$roundedLat}_{$roundedLon}";

        return Cache::remember($cacheKey, 3600, function () use ($lat, $lon) {
            try {
                $response = Http::timeout(3)->get("https://api.open-meteo.com/v1/forecast", [
                    'latitude'        => $lat,
                    'longitude'       => $lon,
                    'current_weather' => true,
                    'timezone'        => 'Asia/Colombo',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $code = $data['current_weather']['weathercode'];
                    $temp = $data['current_weather']['temperature'];

                    return [
                        'temp'        => $temp,
                        'description' => $this->getWeatherDescription($code),
                        'icon'        => $this->getWeatherIcon($code),
                        'is_bad'      => in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99]),
                    ];
                }
            } catch (\Exception $e) {
                return ['error' => true];
            }

            return ['error' => true];
        });
    }

    public function getWeatherDescription($code): string
    {
        $codes = [
            0 => 'Clear sky',
            1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
            45 => 'Foggy', 48 => 'Depositing rime fog',
            51 => 'Light drizzle', 53 => 'Moderate drizzle', 55 => 'Dense drizzle',
            61 => 'Slight rain', 63 => 'Moderate rain', 65 => 'Heavy rain',
            80 => 'Slight rain showers', 81 => 'Moderate rain showers', 82 => 'Violent rain showers',
            95 => 'Thunderstorm', 96 => 'Thunderstorm with slight hail', 99 => 'Thunderstorm with heavy hail',
        ];

        return $codes[$code] ?? 'Cloudy';
    }

    public function getWeatherIcon($code): string
    {
        if ($code == 0) return '☀️';
        if (in_array($code, [1, 2, 3])) return '⛅';
        if (in_array($code, [45, 48])) return '🌫️';
        if (in_array($code, [51, 53, 55, 56, 57])) return '🌦️';
        if (in_array($code, [61, 63, 65, 66, 67, 80, 81, 82])) return '🌧️';
        if (in_array($code, [95, 96, 99])) return '⛈️';

        return '☁️';
    }
}
