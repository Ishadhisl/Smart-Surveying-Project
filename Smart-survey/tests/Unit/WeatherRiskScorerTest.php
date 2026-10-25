<?php

namespace Tests\Unit;

use App\Services\WeatherRiskScorer;
use Tests\TestCase;

class WeatherRiskScorerTest extends TestCase
{
    private WeatherRiskScorer $scorer;
    private array $fixture;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = app(WeatherRiskScorer::class);
        $fixturePath = base_path('tests/Fixtures/weather_fixture.json');
        $this->assertFileExists($fixturePath);
        $this->fixture = json_decode(file_get_contents($fixturePath), true);
    }

    public function test_fixture_daily_expected_risks_match_exactly(): void
    {
        $daily = $this->fixture['api_response']['daily'];
        $expectedPerDay = $this->fixture['expected']['per_day'];

        $result = $this->scorer->evaluateForecast($daily);

        $this->assertCount(count($expectedPerDay), $result['per_day']);

        foreach ($expectedPerDay as $i => $expected) {
            $actual = $result['per_day'][$i];
            $this->assertEquals($expected['date'], $actual['date']);
            $this->assertEquals($expected['weekday'], $actual['weekday']);
            $this->assertEquals(
                $expected['expected_risk'],
                $actual['risk'],
                "Failed risk classification for {$expected['date']} ({$expected['weekday']}): expected {$expected['expected_risk']}, got {$actual['risk']}"
            );
        }
    }

    public function test_boundary_condition_on_day_6_is_strictly_low(): void
    {
        // Day 6 (2026-10-17) sits exactly on the limits: rain = 1.0 mm, wind = 30 km/h.
        // The rule requires limits to be strictly 'greater than', so 1.0 mm and 30 km/h must be Low risk.
        $day6Data = [
            'date'               => '2026-10-17',
            'weather_code'       => 3,
            'precipitation_sum'  => 1.0,
            'wind_speed_10m_max' => 30.0,
            'cloud_cover_mean'   => 40.0,
            'temperature_2m_max' => 30.9,
        ];

        $scored = $this->scorer->scoreDay($day6Data);

        $this->assertEquals('Low', $scored['risk'], 'Boundary values rain=1.0 and wind=30 must be strictly evaluated as Low risk.');
    }

    public function test_best_days_in_order_match_fixture(): void
    {
        $daily = $this->fixture['api_response']['daily'];
        $expectedBest = $this->fixture['expected']['best_days_in_order'];

        $result = $this->scorer->evaluateForecast($daily);

        $this->assertEquals($expectedBest, $result['best_days_in_order']);
    }

    public function test_worst_days_match_fixture(): void
    {
        $daily = $this->fixture['api_response']['daily'];
        $expectedWorst = $this->fixture['expected']['worst_days'];

        $result = $this->scorer->evaluateForecast($daily);

        $this->assertEquals($expectedWorst, $result['worst_days']);
    }

    public function test_thunderstorm_codes_trigger_high_risk(): void
    {
        foreach ([95, 96, 99] as $code) {
            $scored = $this->scorer->scoreDay([
                'weather_code'       => $code,
                'precipitation_sum'  => 0.0,
                'wind_speed_10m_max' => 5.0,
            ]);
            $this->assertEquals('High', $scored['risk']);
            $this->assertLessThanOrEqual(35, $scored['score']);
        }
    }
}
