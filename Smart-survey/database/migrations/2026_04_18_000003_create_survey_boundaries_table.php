<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_boundaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30); // client_sketch, surveyor_final
            $table->integer('version_no')->default(1);
            $table->geometry('geom', 'polygon', 4326);
            $table->decimal('area_sqm', 15, 2);
            $table->decimal('perimeter_m', 15, 2);
            $table->integer('vertex_count');
            $table->string('source', 20)->default('drawn'); // drawn, csv, geojson
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['survey_id', 'kind', 'version_no']);
            $table->spatialIndex('geom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_boundaries');
    }
};
