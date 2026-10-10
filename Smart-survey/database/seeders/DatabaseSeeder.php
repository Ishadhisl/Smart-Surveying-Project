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

        // Surveyor 1
        User::updateOrCreate(['license_number' => 'SURV-123'], [
            'name'            => 'Admin Surveyor',
            'email'           => 'admin@example.com',
            'password'        => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role'            => 'admin',
            'license_number'  => 'SURV-123',
            'whatsapp_number' => '0777000001',
            'language'        => 'en',
        ]);

        // Surveyor 2
        User::updateOrCreate(['license_number' => '6642'], [
            'name'            => 'Surveyor Two',
            'email'           => 'surveyor2@example.com',
            'password'        => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role'            => 'admin',
            'license_number'  => '6642',
            'whatsapp_number' => '0777000002',
            'language'        => 'si',
        ]);

        // Clients from land_surveying.sql
        $clients = [
            ['name' => 'John Client',       'email' => 'client@test.com',             'phone' => '0771234567'],
            ['name' => 'Anushka Umayanga',  'email' => 'anu@gmail.com',               'phone' => '0770087496'],
            ['name' => 'Lasitha Priyasad',  'email' => 'lasithapriyasad3@gmail.com',  'phone' => '0743398128'],
            ['name' => 'Kasun Perera',      'email' => 'kasun@gmail.com',             'phone' => '0715364250'],
            ['name' => 'Namal Godagama',    'email' => 'namalgodagama@gmail.com',     'phone' => '0711846188'],
            ['name' => 'Samitha Nirmani',   'email' => 'samitha@gmail.com',           'phone' => '0715556699'],
            ['name' => 'Nimesha Mihiranga', 'email' => 'nime@gmail.com',              'phone' => '0712889602'],
            ['name' => 'Sayuru Perera',     'email' => 'sayu@gmail.com',              'phone' => '0741535838'],
            ['name' => 'Ishadhi Paranage',  'email' => 'ishadhiparanage@gmail.com',   'phone' => '0719062507'],
            ['name' => 'Janakee Pathiraja', 'email' => 'panjanakee@yahoo.com',        'phone' => '0714474654'],
            ['name' => 'Malithi Silva',     'email' => 'malithisilva@gmail.com',      'phone' => '0764964458'],
        ];

        foreach ($clients as $c) {
            User::updateOrCreate(['email' => $c['email']], [
                'name'            => $c['name'],
                'password'        => Hash::make(env('CLIENT_PASSWORD', 'password')),
                'role'            => 'client',
                'whatsapp_number' => $c['phone'],
                'language'        => 'en',
            ]);
        }

        // ============================================================
        // SURVEYS — adapted from appointments table
        // ============================================================
        $john    = User::where('email', 'client@test.com')->first();
        $anushka = User::where('email', 'anu@gmail.com')->first();
        $lasitha = User::where('email', 'lasithapriyasad3@gmail.com')->first();
        $kasun   = User::where('email', 'kasun@gmail.com')->first();
        $namal   = User::where('email', 'namalgodagama@gmail.com')->first();
        $nimesha = User::where('email', 'nime@gmail.com')->first();
        $ishadhi = User::where('email', 'ishadhiparanage@gmail.com')->first();
        $janakee = User::where('email', 'panjanakee@yahoo.com')->first();
        $malithi = User::where('email', 'malithisilva@gmail.com')->first();

        $surveys = [
            // John — Mapping, 15 Perches
            ['user_id' => $john?->id, 'village' => 'Colombo', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 15, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 20000, 'actual_price' => 25000, 'balance_amount' => 25000,
             'status' => 'mapping', 'payment_status' => 'pending',
             'location_lat' => 6.9271, 'location_lng' => 79.8612, 'scheduled_at' => '2026-05-01 10:00:00'],

            // Anushka — Completed, 10 Perches
            ['user_id' => $anushka?->id, 'village' => 'Kurunegala', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 10, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 25000, 'status' => 'completed', 'payment_status' => 'pending',
             'location_lat' => 7.70895, 'location_lng' => 80.31896, 'scheduled_at' => '2026-04-07 13:00:00'],

            // Lasitha — Completed, 10 Perches, Fully Paid
            ['user_id' => $lasitha?->id, 'village' => 'Hiripitiya', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 10, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 25000, 'actual_price' => 20000, 'balance_amount' => 0,
             'status' => 'completed', 'payment_status' => 'fully_paid', 'final_paid_at' => now(),
             'location_lat' => 6.9271, 'location_lng' => 79.8612, 'scheduled_at' => '2026-04-07 09:00:00'],

            // Kasun — Completed, 15 Acres, Fully Paid
            ['user_id' => $kasun?->id, 'village' => 'Thalduwa', 'purpose' => 'Bank Loan / Deed',
             'size_acres' => 15, 'size_perches' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 3760000, 'actual_price' => 300000, 'balance_amount' => 0,
             'status' => 'completed', 'payment_status' => 'fully_paid', 'final_paid_at' => now(),
             'location_lat' => 6.956077, 'location_lng' => 80.220115, 'scheduled_at' => '2026-04-12 13:15:00'],

            // Namal — Confirmed, 20 Perches
            ['user_id' => $namal?->id, 'village' => 'Ibbagamuwa', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 20, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 40000, 'status' => 'confirmed', 'payment_status' => 'pending',
             'location_lat' => 7.546294, 'location_lng' => 80.444657, 'scheduled_at' => '2026-04-11 12:00:00'],

            // Nimesha — Completed, 30 Perches, Fully Paid
            ['user_id' => $nimesha?->id, 'village' => 'Anuradhapura', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 30, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 55000, 'actual_price' => 55000, 'balance_amount' => 0,
             'status' => 'completed', 'payment_status' => 'fully_paid',
             'advance_paid_at' => now(), 'final_paid_at' => now(),
             'location_lat' => 8.333036, 'location_lng' => 80.412037, 'scheduled_at' => '2026-04-11 13:00:00'],

            // Ishadhi — Completed, 50 Perches, Fully Paid
            ['user_id' => $ishadhi?->id, 'village' => 'Hiripitiya', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 50, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 85000, 'actual_price' => 80000, 'balance_amount' => 0,
             'status' => 'completed', 'payment_status' => 'fully_paid',
             'advance_paid_at' => now(), 'final_paid_at' => now(),
             'location_lat' => 7.654533, 'location_lng' => 80.364784, 'scheduled_at' => '2026-04-13 09:30:00'],

            // Janakee — Pending / Requested, 27 Perches
            ['user_id' => $janakee?->id, 'village' => 'Hiripitiya', 'purpose' => 'Bank Loan / Deed',
             'size_perches' => 27, 'size_acres' => 0, 'lot_count' => 1, 'boundary_points' => 4,
             'estimate_price' => 50500, 'status' => 'requested', 'payment_status' => 'pending',
             'location_lat' => 7.654533, 'location_lng' => 80.364784],

            // Malithi — Confirmed, 20 Acres, Partitioning, Advance Paid
            ['user_id' => $malithi?->id, 'village' => 'Yakkala', 'purpose' => 'Partitioning (Katti Bederima)',
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
