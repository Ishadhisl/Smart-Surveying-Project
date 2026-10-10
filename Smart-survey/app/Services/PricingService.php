<?php

namespace App\Services;

class PricingService
{
    private const MOBILIZATION_FEE = 15000;
    private const TIER1_PERCH_RATE = 850;
    private const TIER2_BASE_FEE = 34000;
    private const TIER2_PERCH_RATE = 400;
    private const FIRST_ACRE_FEE = 65000;
    private const ADDITIONAL_ACRE_FEE = 35000;
    private const TIER3_FRACTIONAL_PERCH_RATE = 200;

    private const RATE_PER_POINT = 1500;
    private const PARTITIONING_LOT_RATE = 6500;
    private const LEGAL_DOCUMENTATION_FEE = 5000;

    /**
     * Calculate estimate price based on survey details.
     */
    public function calculateEstimate(array $data): float
    {
        $price = self::MOBILIZATION_FEE;

        // Size-based pricing (Convert everything to perches)
        $acres = (float) ($data['size_acres'] ?? 0);
        $perches = (float) ($data['size_perches'] ?? 0);
        $totalPerches = ($acres * 160) + $perches;

        if ($totalPerches <= 40) {
            $price += $totalPerches * self::TIER1_PERCH_RATE;
        } elseif ($totalPerches < 160) {
            $price += self::TIER2_BASE_FEE + (($totalPerches - 40) * self::TIER2_PERCH_RATE);
        } else {
            $totalAcres = floor($totalPerches / 160);
            $remainingPerches = $totalPerches - ($totalAcres * 160);
            
            $price += self::FIRST_ACRE_FEE;
            if ($totalAcres > 1) {
                $price += ($totalAcres - 1) * self::ADDITIONAL_ACRE_FEE;
            }
            if ($remainingPerches > 0) {
                $price += $remainingPerches * self::TIER3_FRACTIONAL_PERCH_RATE;
            }
        }

        // Boundary points logic (first 4 free, then per point)
        $boundaryPoints = $data['boundary_points'] ?? 4;
        if ($boundaryPoints > 4) {
            $price += ($boundaryPoints - 4) * self::RATE_PER_POINT;
        }

        // Purpose-based logic
        $purpose = $data['purpose'] ?? 'boundary_marking';
        
        if ($purpose === 'partitioning') {
            $price += ($data['lot_count'] ?? 1) * self::PARTITIONING_LOT_RATE;
        }
        
        if (in_array($purpose, ['court_partition', 'banking'])) {
            $price += self::LEGAL_DOCUMENTATION_FEE;
        }

        return (float) round($price, -2); // Round to nearest 100
    }

    /**
     * Get the required advance payment (1/3 of estimate).
     */
    public function getAdvanceAmount(float $estimate): float
    {
        return round($estimate / 3, -1); // Round to nearest 10
    }

    /**
     * Calculate balance based on actual price and advance paid.
     */
    public function calculateBalance(float $actualPrice, float $advancePaid): float
    {
        return max(0, $actualPrice - $advancePaid);
    }

    /**
     * Calculate the current remaining balance for a survey.
     */
    public function calculateRemainingBalance(\App\Models\Survey $survey, ?float $newActualPrice = null): float
    {
        $basePrice = $newActualPrice ?? ($survey->actual_price ?? $survey->estimate_price);
        
        $paidAmount = \App\Models\Payment::where('survey_id', $survey->id)
            ->whereIn('status', ['AdvancePaid', 'ActualPaid'])
            ->sum('amount');

        return (float) max(0, $basePrice - $paidAmount);
    }
}
