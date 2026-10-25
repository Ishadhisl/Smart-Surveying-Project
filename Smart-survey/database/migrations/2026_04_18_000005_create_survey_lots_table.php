<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boundary_id')->constrained('survey_boundaries')->cascadeOnDelete();
            $table->integer('proposal_no')->default(1);
            $table->integer('lot_no');
            $table->geometry('geom', 'polygon', 4326);
            $table->string('type', 30)->default('residential'); // residential, access_road, reserve
            $table->decimal('area_sqm', 15, 2);
            $table->string('status', 20)->default('proposed'); // proposed, final
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['survey_id', 'boundary_id', 'proposal_no', 'lot_no']);
            $table->spatialIndex('geom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_lots');
    }
};
