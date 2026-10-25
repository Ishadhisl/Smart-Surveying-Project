<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // building, tree, well, canal, stream, wall, road, other
            $table->geometry('geom', 'geometry', 4326);
            $table->string('label', 100)->default('Feature');
            $table->decimal('clearance_m', 6, 2)->nullable();
            $table->string('source', 20)->default('surveyor'); // surveyor only (osm removed as required)
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->spatialIndex('geom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_features');
    }
};
