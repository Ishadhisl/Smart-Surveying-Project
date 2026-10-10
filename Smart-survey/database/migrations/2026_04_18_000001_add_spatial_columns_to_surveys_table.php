<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->geometry('boundary_polygon', 'polygon', 4326)->nullable();
            $table->decimal('computed_area_sqm', 15, 2)->nullable();
            $table->decimal('computed_perimeter_m', 15, 2)->nullable();
            $table->spatialIndex('boundary_polygon');
        });
    }

    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropSpatialIndex(['boundary_polygon']);
            $table->dropColumn(['boundary_polygon', 'computed_area_sqm', 'computed_perimeter_m']);
        });
    }
};
