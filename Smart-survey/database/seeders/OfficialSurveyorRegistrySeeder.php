<?php

namespace Database\Seeders;

use App\Models\OfficialSurveyorRegistry;
use Illuminate\Database\Seeder;

class OfficialSurveyorRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $surveyors = [
            [
                'license_number'  => '6642',
                'full_name'       => 'A S Kumara Paranage',
                'initials_name'   => 'A.S.K. Paranage',
                'status'          => 'active',
                'district'        => 'Kurunegala',
                'phone'           => '0717906355',
                'registered_year' => '2015',
            ],
            [
                'license_number'  => 'SURV-7788',
                'full_name'       => 'Licensed Land Surveyor',
                'initials_name'   => 'L.L. Surveyor',
                'status'          => 'active',
                'district'        => 'Colombo',
                'phone'           => '0770000000',
                'registered_year' => '2018',
            ],
            [
                'license_number'  => 'SURV-123',
                'full_name'       => 'Sam Surveyor',
                'initials_name'   => 'S. Surveyor',
                'status'          => 'active',
                'district'        => 'Kandy',
                'phone'           => '0777654321',
                'registered_year' => '2019',
            ],
            [
                'license_number'  => 'LS-2024-001',
                'full_name'       => 'Dharmasiri Jayasinghe',
                'initials_name'   => 'D. Jayasinghe',
                'status'          => 'active',
                'district'        => 'Gampaha',
                'phone'           => '0712345678',
                'registered_year' => '2024',
            ],
        ];

        foreach ($surveyors as $s) {
            OfficialSurveyorRegistry::updateOrCreate(
                ['license_number' => $s['license_number']],
                $s
            );
        }
    }
}
