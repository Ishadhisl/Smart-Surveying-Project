<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WeatherService
{
    /**
     * Get weather description and icon based on coordinates
     */
    public function getWeather($lat, $lon, $scheduled_at = null)
    {
        $cacheKey = "weather_{$lat}_{$lon}_" . ($scheduled_at ? \Carbon\Carbon::parse($scheduled_at)->format('Y-m-d') : 'current');
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($lat, $lon, $scheduled_at) {
            try {
                if ($scheduled_at) {
                    $scheduleTime = \Carbon\Carbon::parse($scheduled_at);
                    
                    if ($scheduleTime->gt(now()->addDays(16))) {
                        return ['error' => true];
                    }

                    $dateString = $scheduleTime->format('Y-m-d');
                    $targetHourString = $scheduleTime->format('Y-m-d\TH:00');

                    $response = Http::timeout(3)->get("https://api.open-meteo.com/v1/forecast", [
                        'latitude' => $lat,
                        'longitude' => $lon,
                        'hourly' => 'temperature_2m,weathercode',
                        'start_date' => $dateString,
                        'end_date' => $dateString,
                        'timezone' => 'auto'
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        
                        if (isset($data['hourly']['time']) && is_array($data['hourly']['time'])) {
                            // Find the index that matches the target hour
                            $index = array_search($targetHourString, $data['hourly']['time']);
                            
                            // If exact hour not found, fallback to 08:00 of that day (morning)
                            if ($index === false) {
                                $targetHourStringFallback = $dateString . 'T08:00';
                                $index = array_search($targetHourStringFallback, $data['hourly']['time']);
                            }
                            
                            // If still not found, just take a reasonable morning time if array matches
                            if ($index === false) {
                                $index = 8; // 08:00 index in a daily hourly array
                            }

                            if (isset($data['hourly']['weathercode'][$index])) {
                                $code = $data['hourly']['weathercode'][$index];
                                $temp = $data['hourly']['temperature_2m'][$index];
                                
                                return [
                                    'temp' => $temp,
                                    'description' => $this->getWeatherDescription($code),
                                    'icon' => $this->getWeatherIcon($code),
                                    'is_bad' => in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99])
                                ];
                            }
                        }
                    }
                }

                // Fallback: Current weather (if no scheduled_at or if hourly API fails due to being out of range)
                $response = Http::timeout(3)->get("https://api.open-meteo.com/v1/forecast", [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current_weather' => true,
                    'timezone' => 'auto'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $code = $data['current_weather']['weathercode'];
                    $temp = $data['current_weather']['temperature'];
                    
                    return [
                        'temp' => $temp,
                        'description' => $this->getWeatherDescription($code),
                        'icon' => $this->getWeatherIcon($code),
                        'is_bad' => in_array($code, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82, 95, 96, 99])
                    ];
                }
            } catch (\Exception $e) {
                return ['error' => true];
            }

            return ['error' => true];
        });
    }

    private function getWeatherDescription($code)
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

    private function getWeatherIcon($code)
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
