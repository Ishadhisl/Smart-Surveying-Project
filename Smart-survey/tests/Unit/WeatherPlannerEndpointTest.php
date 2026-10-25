<?php

namespace Tests\Unit;

use App\Http\Controllers\SurveyController;
use App\Models\Survey;
use App\Services\PricingService;
use App\Services\SurveyService;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WeatherPlannerEndpointTest extends TestCase
{
    private SurveyController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('survey.weather.demo_mode', true);

        $weatherService = app(WeatherService::class);
        $surveyService = $this->createMock(SurveyService::class);
        $pricingService = $this->createMock(PricingService::class);

        $this->controller = new SurveyController($surveyService, $pricingService, $weatherService);
    }

    public function test_weather_forecast_returns_seven_day_strip(): void
    {
        $survey = new Survey();
        $survey->id = 1;
        $survey->location_lat = 7.2906;
        $survey->location_lng = 80.6337;

        $request = Request::create('/admin/survey/1/weather-forecast', 'GET');
        $response = $this->controller->weatherForecast($request, $survey);

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertArrayHasKey('per_day', $data);
        $this->assertCount(7, $data['per_day']);
        $this->assertTrue($data['is_demo']);
        $this->assertArrayHasKey('best_days_in_order', $data);
        $this->assertArrayHasKey('worst_days', $data);
        $this->assertArrayHasKey('disclaimer', $data);
        $this->assertStringContainsString('Planning defaults', $data['disclaimer']);

        // Check each day has enriched UI attributes
        $firstDay = $data['per_day'][0];
        $this->assertArrayHasKey('risk', $firstDay);
        $this->assertArrayHasKey('score', $firstDay);
        $this->assertArrayHasKey('icon', $firstDay);
        $this->assertArrayHasKey('description', $firstDay);
        $this->assertArrayHasKey('is_recommended', $firstDay);
        $this->assertTrue($firstDay['is_recommended']);
    }

    public function test_check_weather_for_valid_date(): void
    {
        $request = Request::create('/admin/weather-check', 'GET', [
            'lat'  => 7.2906,
            'lng'  => 80.6337,
            'date' => '2026-10-12',
        ]);

        $response = $this->controller->checkWeather($request);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertEquals('available', $data['status']);
        $this->assertEquals('Low', $data['risk']);
        $this->assertEquals('2026-10-12', $data['date']);
    }

    public function test_check_weather_missing_coordinates(): void
    {
        $request = Request::create('/admin/weather-check', 'GET', []);
        $response = $this->controller->checkWeather($request);
        $this->assertEquals(400, $response->getStatusCode());
    }
}
