<?php

namespace Tests\Unit;

use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private WeatherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WeatherService::class);
        Cache::flush();
    }

    public function test_demo_mode_serves_fixture_offline(): void
    {
        Config::set('survey.weather.demo_mode', true);

        // Ensure no external HTTP requests are made
        Http::preventStrayRequests();

        $forecast = $this->service->getDailyForecast(7.2906, 80.6337);

        $this->assertTrue($forecast['is_demo']);
        $this->assertCount(7, $forecast['per_day']);
        $this->assertEquals(['2026-10-12', '2026-10-13', '2026-10-17'], $forecast['best_days_in_order']);
    }

    public function test_dates_beyond_7_day_horizon_return_unavailable(): void
    {
        Config::set('survey.weather.demo_mode', false);
        Http::preventStrayRequests();

        // 10 days in the future
        $farFuture = Carbon::now()->addDays(10)->format('Y-m-d');

        $result = $this->service->getWeatherForDate(7.2906, 80.6337, $farFuture);

        $this->assertEquals('unavailable', $result['status']);
        $this->assertStringContainsString('7 days', $result['message']);
    }

    public function test_live_forecast_with_http_fake(): void
    {
        Config::set('survey.weather.demo_mode', false);

        $fakeDaily = [
            'time'               => ['2026-10-20', '2026-10-21'],
            'weather_code'       => [0, 95],
            'precipitation_sum'  => [0.0, 15.0],
            'wind_speed_10m_max' => [10.0, 45.0],
            'cloud_cover_mean'   => [10.0, 95.0],
            'temperature_2m_max' => [32.0, 26.0],
        ];

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'daily' => $fakeDaily,
            ], 200),
        ]);

        $forecast = $this->service->getDailyForecast(7.2906, 80.6337);

        $this->assertFalse($forecast['is_demo']);
        $this->assertCount(2, $forecast['per_day']);
        $this->assertEquals('Low', $forecast['per_day'][0]['risk']);
        $this->assertEquals('High', $forecast['per_day'][1]['risk']);
        $this->assertEquals(['2026-10-20', '2026-10-21'], $forecast['best_days_in_order']);
    }

    public function test_http_failure_caches_sentinel(): void
    {
        Config::set('survey.weather.demo_mode', false);

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response('Server Error', 500),
        ]);

        $forecast = $this->service->getDailyForecast(7.2906, 80.6337);

        $this->assertTrue($forecast['error']);

        // Second call should hit cache sentinel without re-querying
        $cached = $this->service->getDailyForecast(7.2906, 80.6337);
        $this->assertTrue($cached['error']);
    }
}
