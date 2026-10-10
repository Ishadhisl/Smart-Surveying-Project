<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $user = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test_reg@example.com',
        'password' => bcrypt('password'),
        'role' => 'client',
        'whatsapp_number' => '1234567890',
        'language' => 'en'
    ]);
    echo "SUCCESS: User created with ID " . $user->id;
    $user->delete();
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
