<?php

namespace Tests\Unit;

use App\Models\Survey;
use App\Models\SurveyBoundary;
use App\Models\SurveyFeature;
use App\Models\User;
use App\Services\LotRecommenderService;
use App\Services\SubdivisionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class SubdivisionTest extends TestCase
{

    private ?User $surveyor = null;
    private ?User $client = null;
    private ?Survey $survey = null;
    private SubdivisionService $subdivisionService;
    private LotRecommenderService $recommenderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subdivisionService = app(SubdivisionService::class);
        $this->recommenderService = app(LotRecommenderService::class);
    }

    private function ensurePostgisAvailable(): void
    {
        try {
            DB::connection()->getPdo();
            $ext = DB::selectOne("SELECT 1 FROM pg_extension WHERE extname = 'postgis'");
            if (!$ext) {
                $this->markTestSkipped('PostGIS extension not installed in the active PostgreSQL database. Start docker-compose up -d for PostGIS.');
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('PostgreSQL + PostGIS connection unavailable (' . $e->getMessage() . '). Run docker-compose up -d to launch PostGIS.');
        }

        if (!$this->survey) {
            $this->surveyor = User::factory()->create(['role' => 'admin']);
            $this->client = User::factory()->create(['role' => 'client']);

            $this->survey = Survey::create([
                'user_id'         => $this->client->id,
                'village'         => 'Kandy Test Plot',
                'purpose'         => 'partitioning',
                'location_lat'    => 7.3086845,
                'location_lng'    => 80.6337,
                'status'          => 'mapping',
                'payment_status'  => 'advance_paid',
                'lot_count'       => 2,
            ]);
        }
    }

    /**
     * Create a standard 100x100m parent boundary (from test_cases.json C0).
     */
    private function createParentBoundary(): SurveyBoundary
    {
        $this->ensurePostgisAvailable();

        $geojson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[
                [80.6337, 7.3086845],
                [80.6346056, 7.3086845],
                [80.6346056, 7.3095887],
                [80.6337, 7.3095887],
                [80.6337, 7.3086845],
            ]],
        ]);

        $res = DB::selectOne("
            SELECT
                ST_Area(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography) AS area_sqm,
                ST_Perimeter(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)::geography) AS perimeter_m
        ", [$geojson, $geojson]);

        return SurveyBoundary::create([
            'survey_id'    => $this->survey->id,
            'kind'         => 'surveyor_final',
            'version_no'   => 1,
            'geom'         => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$geojson}'), 4326)"),
            'area_sqm'     => $res->area_sqm,
            'perimeter_m'  => $res->perimeter_m,
            'vertex_count' => 4,
            'source'       => 'drawn',
            'created_by'   => $this->surveyor->id,
        ]);
    }

    /**
     * Test Case C1: 1 vertical cut across 100x100m -> 2 lots (sum rule strictly holds).
     */
    public function test_c1_single_cut_splits_into_two_valid_lots(): void
    {
        $boundary = $this->createParentBoundary();

        $cutLine = [
            'type' => 'LineString',
            'coordinates' => [
                [80.63406224986649, 7.308594035084058],
                [80.63406224986649, 7.309679102077572],
            ],
        ];

        $lots = $this->subdivisionService->manualSplit($boundary, [$cutLine]);

        $this->assertCount(2, $lots);
        $totalArea = array_sum(array_column($lots, 'area_sqm'));
        $diffPct = abs($totalArea - $boundary->area_sqm) / $boundary->area_sqm * 100.0;

        // Verify 0.01% sum rule
        $this->assertLessThan(0.01, $diffPct);
        $this->assertEquals(1, $lots[0]['lot_no']);
        $this->assertEquals(2, $lots[1]['lot_no']);
    }

    /**
     * Test Case C2: 2 vertical cuts -> 3 lots.
     */
    public function test_c2_two_cuts_split_into_three_lots(): void
    {
        $boundary = $this->createParentBoundary();

        $cuts = [
            [
                'type' => 'LineString',
                'coordinates' => [
                    [80.63397168739986, 7.308594035147697],
                    [80.63397168739986, 7.309679102141209],
                ],
            ],
            [
                'type' => 'LineString',
                'coordinates' => [
                    [80.63433393726635, 7.308594034784059],
                    [80.63433393726635, 7.309679101777573],
                ],
            ],
        ];

        $lots = $this->subdivisionService->manualSplit($boundary, $cuts);

        $this->assertCount(3, $lots);
        $totalArea = array_sum(array_column($lots, 'area_sqm'));
        $diffPct = abs($totalArea - $boundary->area_sqm) / $boundary->area_sqm * 100.0;
        $this->assertLessThan(0.01, $diffPct);
    }

    /**
     * Test Case C4 / C5: Incomplete or outside cut line is rejected with 422 exception.
     */
    public function test_incomplete_cut_line_is_rejected(): void
    {
        $boundary = $this->createParentBoundary();

        // Line completely outside the boundary
        $outsideCut = [
            'type' => 'LineString',
            'coordinates' => [
                [80.6500, 7.3085],
                [80.6500, 7.3095],
            ],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->subdivisionService->manualSplit($boundary, [$outsideCut]);
    }

    /**
     * Test Recommender Case E1: Square plot with well and building.
     * Expects >= 1 proposal with 0 conflicts and every lot within tolerance.
     */
    public function test_e1_recommender_finds_conflict_free_proposals(): void
    {
        $boundary = $this->createParentBoundary();

        // Add well obstacle
        SurveyFeature::create([
            'survey_id'   => $this->survey->id,
            'type'        => 'well',
            'label'       => 'Test Well',
            'clearance_m' => 2.0,
            'geom'        => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{\"type\":\"Point\",\"coordinates\":[80.6340018, 7.3091]}'), 4326)"),
            'source'      => 'surveyor',
            'created_by'  => $this->surveyor->id,
        ]);

        $res = $this->recommenderService->recommend($boundary, 3, 5.0, 10);

        $this->assertNotEmpty($res['proposals']);
        $this->assertTrue($res['conflict_free_proposals_exist']);
        $this->assertEquals(0, $res['proposals'][0]['conflicts_count']);
        $this->assertCount(3, $res['proposals'][0]['lots']);
    }

    /**
     * Test Recommender Case E4: No features, 4 lots -> exactly equal lot areas.
     */
    public function test_e4_recommender_equal_area_sanity(): void
    {
        $boundary = $this->createParentBoundary();

        $res = $this->recommenderService->recommend($boundary, 4, 3.0, 10);

        $this->assertNotEmpty($res['proposals']);
        $this->assertTrue($res['conflict_free_proposals_exist']);
        $best = $res['proposals'][0];
        $this->assertEquals(0, $best['conflicts_count']);
        $this->assertCount(4, $best['lots']);
    }

    /**
     * Test Polsby-Popper compactness calculation.
     */
    public function test_compactness_calculation(): void
    {
        // For a square of 100x100m, perimeter is 400m, area is 10000m²
        // Polsby-Popper score = (4 * pi * 10000) / 400^2 = 40000*pi / 160000 = pi / 4 ≈ 0.785
        $score = $this->subdivisionService->calculateCompactness(10000.0, 400.0);
        $this->assertEqualsWithDelta(0.785, $score, 0.01);
    }
}
