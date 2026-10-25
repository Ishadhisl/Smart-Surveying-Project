<?php

namespace App\Services;

use App\Models\OfficialSurveyorRegistry;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SurveyorVerificationService
{
    /**
     * Verify surveyor license and name against official Land Survey Council registry.
     *
     * @param string $licenseNumber
     * @param string $submittedName
     * @return array{valid: bool, license_number: ?string, official_name: ?string, district: ?string, message: string}
     */
    public function verify(string $licenseNumber, string $submittedName): array
    {
        $cleanLicense = $this->normalizeLicense($licenseNumber);
        $cleanSubmittedName = trim($submittedName);

        // 1. Optional Live External Registry API check (if configured in environment)
        $externalResult = $this->checkLiveExternalRegistry($cleanLicense);
        if ($externalResult !== null && $externalResult['valid']) {
            if ($this->doesNameMatch($cleanSubmittedName, $externalResult['official_name'])) {
                return $externalResult;
            }
        }

        // 2. Query Official Registered Surveyor Dataset
        $registryEntry = OfficialSurveyorRegistry::where('license_number', $cleanLicense)
            ->orWhere('license_number', ltrim($cleanLicense, '0'))
            ->orWhereRaw('LOWER(license_number) = ?', [strtolower($cleanLicense)])
            ->first();

        // Fallback: check if license matches without common prefix (e.g., 'LS-' or 'SURV-')
        if (!$registryEntry && preg_match('/(?:ls|surv)[-_ ]*(.+)/i', $cleanLicense, $m)) {
            $stripped = trim($m[1]);
            $registryEntry = OfficialSurveyorRegistry::where('license_number', $stripped)
                ->orWhereRaw('LOWER(license_number) = ?', [strtolower($stripped)])
                ->first();
        }

        if (!$registryEntry) {
            return [
                'valid' => false,
                'license_number' => null,
                'official_name' => null,
                'district' => null,
                'message' => "The License Number '{$licenseNumber}' was not found in the official Land Survey Council Registry.",
            ];
        }

        if (strtolower($registryEntry->status) !== 'active') {
            return [
                'valid' => false,
                'license_number' => $registryEntry->license_number,
                'official_name' => $registryEntry->full_name,
                'district' => $registryEntry->district,
                'message' => "The License Number '{$licenseNumber}' is currently marked as {$registryEntry->status} in the official registry.",
            ];
        }

        // 3. Verify submitted name against official registered name
        $nameMatches = $this->doesNameMatch($cleanSubmittedName, $registryEntry->full_name) 
                    || ($registryEntry->initials_name && $this->doesNameMatch($cleanSubmittedName, $registryEntry->initials_name));

        if (!$nameMatches) {
            return [
                'valid' => false,
                'license_number' => $registryEntry->license_number,
                'official_name' => $registryEntry->full_name,
                'district' => $registryEntry->district,
                'message' => "Submitted name '{$submittedName}' does not match the official name on file ('{$registryEntry->full_name}') for license {$registryEntry->license_number}.",
            ];
        }

        return [
            'valid' => true,
            'license_number' => $registryEntry->license_number,
            'official_name' => $registryEntry->full_name,
            'district' => $registryEntry->district,
            'message' => 'Successfully verified against Land Survey Council Official Registry.',
        ];
    }

    /**
     * Clean and normalize license number format.
     */
    public function normalizeLicense(string $license): string
    {
        $normalized = trim($license);
        return preg_replace('/\s+/', '', $normalized);
    }

    /**
     * Compare submitted name tokens with official name to allow reasonable variations.
     * (e.g., "A.S.K. Paranage", "A S Kumara Paranage", "Kumara Paranage")
     */
    protected function doesNameMatch(string $submitted, string $official): bool
    {
        $cleanSubmitted = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $submitted));
        $cleanOfficial = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', ' ', $official));

        $submittedTokens = array_filter(explode(' ', $cleanSubmitted), fn($t) => strlen($t) > 1);
        $officialTokens = array_filter(explode(' ', $cleanOfficial), fn($t) => strlen($t) > 1);

        if (empty($submittedTokens) || empty($officialTokens)) {
            return false;
        }

        // Check if primary surname or key name token matches
        $intersection = array_intersect($submittedTokens, $officialTokens);

        // Also check if any token is a substring of the other
        if (empty($intersection)) {
            foreach ($submittedTokens as $sToken) {
                foreach ($officialTokens as $oToken) {
                    if (str_contains($oToken, $sToken) || str_contains($sToken, $oToken)) {
                        return true;
                    }
                }
            }
            return false;
        }

        return count($intersection) > 0;
    }

    /**
     * Optional live HTTP lookup from an external government portal or API.
     */
    protected function checkLiveExternalRegistry(string $license): ?array
    {
        $apiUrl = config('services.surveyor_registry.api_url');
        if (!$apiUrl) {
            return null;
        }

        try {
            $response = Http::timeout(2)->get($apiUrl, ['license' => $license]);
            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['found']) && !empty($data['active'])) {
                    return [
                        'valid' => true,
                        'license_number' => $data['license_number'] ?? $license,
                        'official_name' => $data['name'] ?? null,
                        'district' => $data['district'] ?? null,
                        'message' => 'Verified via Live Land Survey Council Gateway',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('Surveyor live registry lookup skipped: ' . $e->getMessage());
        }

        return null;
    }
}
