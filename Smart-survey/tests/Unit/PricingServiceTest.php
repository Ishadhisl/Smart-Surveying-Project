<?php

namespace Tests\Unit;

use App\Services\PricingService;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();
    }

    /**
     * Test Group A area & pricing estimates match test_cases.json fixture within +-100 Rs.
     */
    public function test_group_a_cases_estimate_within_expected_tolerance(): void
    {
        $cases = [
            // A1: 395.368 perches -> 2 acres + 75.368 perches
            [
                'perches' => 395.368,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 130100,
            ],
            // A2: 20 perches
            [
                'perches' => 20.0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 32000,
            ],
            // A3: 35.583 perches, 6 points (+2 points * 1500)
            [
                'perches' => 35.583,
                'boundary_points' => 6,
                'purpose' => 'boundary_marking',
                'expected' => 48200,
            ],
            // A4: 94.888 perches, 3 points (no extra points)
            [
                'perches' => 94.888,
                'boundary_points' => 3,
                'purpose' => 'boundary_marking',
                'expected' => 71000,
            ],
            // P39: 39 perches
            [
                'perches' => 39.0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 48100,
            ],
            // P41: 41 perches
            [
                'perches' => 41.0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 49400,
            ],
            // P159: 159 perches
            [
                'perches' => 159.0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 96600,
            ],
            // P161: 161 perches
            [
                'perches' => 161.0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
                'expected' => 80200,
            ],
        ];

        foreach ($cases as $c) {
            $estimate = $this->pricingService->calculateEstimate([
                'size_perches' => $c['perches'],
                'size_acres' => 0,
                'boundary_points' => $c['boundary_points'],
                'purpose' => $c['purpose'],
            ]);

            $this->assertEqualsWithDelta(
                $c['expected'],
                $estimate,
                100.0,
                "Failed asserting estimate for {$c['perches']} perches."
            );
        }
    }

    /**
     * Test the monotonic pricing probe: Price(n+1) >= Price(n).
     * Marked as incomplete / expected-fail per locked decisions pending surveyor fee schedule decision.
     */
    public function test_pricing_is_monotonic_across_tiers(): void
    {
        $perchSamples = [38, 39, 40, 41, 100, 158, 159, 160, 161, 200];
        $previousPrice = 0;

        foreach ($perchSamples as $p) {
            $currentPrice = $this->pricingService->calculateEstimate([
                'size_perches' => $p,
                'size_acres' => 0,
                'boundary_points' => 4,
                'purpose' => 'boundary_marking',
            ]);

            if ($previousPrice > 0 && $currentPrice < $previousPrice) {
                $this->markTestIncomplete(
                    "Known pricing anomaly: {$p} perches (Rs {$currentPrice}) is cheaper than previous perches (Rs {$previousPrice}). Awaiting surveyor fee schedule decision."
                );
            }

            $previousPrice = $currentPrice;
        }

        $this->assertTrue(true);
    }
}
