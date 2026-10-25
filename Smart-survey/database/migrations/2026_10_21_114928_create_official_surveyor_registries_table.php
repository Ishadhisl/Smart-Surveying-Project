<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('official_surveyor_registries', function (Blueprint $table) {
            $table->id();
            $table->string('license_number')->unique()->index();
            $table->string('full_name');
            $table->string('initials_name')->nullable();
            $table->string('status')->default('active'); // active, suspended, retired
            $table->string('district')->nullable();
            $table->string('phone')->nullable();
            $table->string('registered_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('official_surveyor_registries');
    }
};
