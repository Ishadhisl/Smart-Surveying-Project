<?php

namespace App\Services;

use Carbon\Carbon;

class WeatherRiskScorer
{
    /**
     * Score a single day forecast.
     *
     * Thresholds from config('survey.weather'):
     * High:   thunder code in (95,96,99) OR rain > 5.0mm OR wind > 40.0km/h
     * Medium: rain > 1.0mm OR wind > 30.0km/h
     * Low:    otherwise (limits are strictly 'greater than': rain=1.0 and wind=30 are Low)
     *
     * @param array $dayData Keys: date, weather_code, precipitation_sum, wind_speed_10m_max, cloud_cover_mean, temperature_2m_max
     * @return array
     */
    public function scoreDay(array $dayData): array
    {
        $code = (int) ($dayData['weather_code'] ?? 0);
        $rain = (float) ($dayData['precipitation_sum'] ?? 0.0);
        $wind = (float) ($dayData['wind_speed_10m_max'] ?? 0.0);
        $cloud = (float) ($dayData['cloud_cover_mean'] ?? 0.0);
        $temp = (float) ($dayData['temperature_2m_max'] ?? 25.0);
        $dateStr = $dayData['date'] ?? now()->format('Y-m-d');
        $date = Carbon::parse($dateStr);

        $highCodes = config('survey.weather.high_risk_weather_codes', [95, 96, 99]);
        $highRain  = config('survey.weather.high_rain_threshold_mm', 5.0);
        $highWind  = config('survey.weather.high_wind_threshold_kmh', 40.0);
        $medRain   = config('survey.weather.med_rain_threshold_mm', 1.0);
        $medWind   = config('survey.weather.med_wind_threshold_kmh', 30.0);

        $reasons = [];
        $risk = 'Low';

        if (in_array($code, $highCodes)) {
            $risk = 'High';
            $reasons[] = "Severe weather/thunderstorm hazard (WMO code {$code})";
        }

        if ($rain > $highRain) {
            $risk = 'High';
            $reasons[] = sprintf("Heavy rainfall forecast (%.1f mm exceeds %.1f mm threshold)", $rain, $highRain);
        }

        if ($wind > $highWind) {
            $risk = 'High';
            $reasons[] = sprintf("High wind hazard (%.1f km/h exceeds %.1f km/h threshold)", $wind, $highWind);
        }

        if ($risk !== 'High') {
            if ($rain > $medRain) {
                $risk = 'Medium';
                $reasons[] = sprintf("Moderate rainfall expected (%.1f mm)", $rain);
            }
            if ($wind > $medWind) {
                $risk = 'Medium';
                $reasons[] = sprintf("Elevated wind speed (%.1f km/h)", $wind);
            }
        }

        if ($risk === 'Low') {
            $reasons[] = "Optimal field surveying conditions with minimal precipitation and manageable winds";
        }

        // Calculate a 0-100 numerical suitability score
        $score = 100.0;
        if ($risk === 'High') {
            $score = max(10.0, 35.0 - ($rain * 0.5) - ($wind * 0.2));
        } elseif ($risk === 'Medium') {
            $score = max(40.0, 70.0 - ($rain * 3.0) - ($wind * 0.5));
        } else {
            $score = max(75.0, 100.0 - ($rain * 5.0) - ($wind * 0.4));
        }

        return [
            'date'                  => $dateStr,
            'weekday'               => $date->format('l'),
            'risk'                  => $risk,
            'score'                 => (int) round($score),
            'reasons'               => $reasons,
            'weather_code'          => $code,
            'precipitation_sum'     => $rain,
            'wind_speed_10m_max'    => $wind,
            'cloud_cover_mean'      => $cloud,
            'temperature_2m_max'    => $temp,
        ];
    }

    /**
     * Evaluate a 7-day forecast array and produce structured recommendations.
     *
     * @param array $daily Open-Meteo daily structure with time, weather_code, precipitation_sum, wind_speed_10m_max, cloud_cover_mean, temperature_2m_max
     * @return array
     */
    public function evaluateForecast(array $daily): array
    {
        $times = $daily['time'] ?? [];
        $perDay = [];

        foreach ($times as $i => $date) {
            $dayData = [
                'date'               => $date,
                'weather_code'       => $daily['weather_code'][$i] ?? 0,
                'precipitation_sum'  => $daily['precipitation_sum'][$i] ?? 0.0,
                'wind_speed_10m_max' => $daily['wind_speed_10m_max'][$i] ?? 0.0,
                'cloud_cover_mean'   => $daily['cloud_cover_mean'][$i] ?? 0.0,
                'temperature_2m_max' => $daily['temperature_2m_max'][$i] ?? 0.0,
            ];
            $perDay[] = $this->scoreDay($dayData);
        }

        // Rank days by: Risk (Low=1, Medium=2, High=3), then rain ASC, then wind ASC
        $riskOrder = ['Low' => 1, 'Medium' => 2, 'High' => 3];
        $sorted = $perDay;
        usort($sorted, function ($a, $b) use ($riskOrder) {
            $ra = $riskOrder[$a['risk']] ?? 99;
            $rb = $riskOrder[$b['risk']] ?? 99;
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            if ($a['precipitation_sum'] !== $b['precipitation_sum']) {
                return $a['precipitation_sum'] <=> $b['precipitation_sum'];
            }
            return $a['wind_speed_10m_max'] <=> $b['wind_speed_10m_max'];
        });

        // Top 3 best dates
        $bestDaysInOrder = array_slice(array_column($sorted, 'date'), 0, 3);

        // Worst days (High risk days, or lowest scored days)
        $highRiskDays = array_values(array_filter($perDay, fn($d) => $d['risk'] === 'High'));
        $worstDays = array_column($highRiskDays, 'date');

        return [
            'per_day'             => $perDay,
            'best_days_in_order'  => $bestDaysInOrder,
            'worst_days'          => $worstDays,
            'top_recommendation'  => $sorted[0] ?? null,
        ];
    }
}
