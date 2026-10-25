<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Survey;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // USERS — from land_surveying.sql
        // role 'surveyor' → 'admin'  (Laravel code uses 'admin')
        // phone → whatsapp_number
        // ALL passwords set to: password
        // ============================================================

        // ============================================================
        // USERS — Seeded from environment or secure defaults (No real PII)
        // Single Surveyor (role 'admin') + Demo Clients
        // ============================================================

        // Surveyor (Admin)
        User::updateOrCreate(['email' => env('SURVEYOR_EMAIL', 'surveyor@example.com')], [
            'name'            => env('SURVEYOR_NAME', 'Licensed Surveyor'),
            'email'           => env('SURVEYOR_EMAIL', 'surveyor@example.com'),
            'password'        => Hash::make(env('SURVEYOR_PASSWORD', 'SecureSurveyorPass123!')),
            'role'            => 'admin',
            'license_number'  => env('SURVEYOR_LICENSE', 'SURV-7788'),
            'whatsapp_number' => '0770000000',
            'language'        => 'en',
        ]);

        // Demo Clients
        $clients = [
            ['name' => 'Demo Client 1', 'email' => 'client1@example.com', 'phone' => '0710000001'],
            ['name' => 'Demo Client 2', 'email' => 'client2@example.com', 'phone' => '0710000002'],
            ['name' => 'Demo Client 3', 'email' => 'client3@example.com', 'phone' => '0710000003'],
            ['name' => 'Demo Client 4', 'email' => 'client4@example.com', 'phone' => '0710000004'],
        ];

        foreach ($clients as $c) {
            User::updateOrCreate(['email' => $c['email']], [
                'name'            => $c['name'],
                'password'        => Hash::make(env('CLIENT_PASSWORD', 'ClientSecretPass123!')),
                'role'            => 'client',
                'whatsapp_number' => $c['phone'],
                'language'        => 'en',
            ]);
        }

        // ============================================================
        // SURVEYS — Seeded with synthetic demo records
        // ============================================================
        $client1 = User::where('email', 'client1@example.com')->first();
        $client2 = User::where('email', 'client2@example.com')->first();
        $client3 = User::where('email', 'client3@example.com')->first();
        $client4 = User::where('email', 'client4@example.com')->first();

        $surveys = [
            // Client 1 — Mapping, 15 Perches
            ['user_id' => $client1?->id, 'village' => 'Colombo', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 15, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 20000, 'actual_price' => 25000, 'balance_amount' => 25000,
             'status' => 'mapping', 'payment_status' => 'pending',
             'location_lat' => 6.9271, 'location_lng' => 79.8612, 'scheduled_at' => '2026-05-01 10:00:00'],

            // Client 2 — Confirmed, 20 Perches
            ['user_id' => $client2?->id, 'village' => 'Ibbagamuwa', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 20, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 40000, 'status' => 'confirmed', 'payment_status' => 'pending',
             'location_lat' => 7.546294, 'location_lng' => 80.444657, 'scheduled_at' => '2026-04-11 12:00:00'],

            // Client 3 — Pending / Requested, 27 Perches
            ['user_id' => $client3?->id, 'village' => 'Hiripitiya', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 27, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 50500, 'status' => 'requested', 'payment_status' => 'pending',
             'location_lat' => 7.654533, 'location_lng' => 80.364784],

            // Client 4 — Confirmed, 20 Acres, Partitioning, Advance Paid
            ['user_id' => $client4?->id, 'village' => 'Yakkala', 'purpose' => 'Partitioning (Katti Bederima)',
             'size_acres' => 20, 'size_perches' => 0, 'lot_count' => 4, 'boundary_points' => 4,
             'estimate_price' => 3220000, 'status' => 'confirmed', 'payment_status' => 'advance_paid',
             'advance_paid_at' => now(),
             'location_lat' => 7.087041, 'location_lng' => 80.033898, 'scheduled_at' => '2026-04-16 12:30:00'],
        ];

        foreach ($surveys as $s) {
            if ($s['user_id']) {
                Survey::create($s);
            }
        }
    }
}
