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
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('village');
            $table->string('purpose');
            $table->string('other_purpose')->nullable();
            $table->decimal('size_acres', 10, 2)->default(0);
            $table->decimal('size_perches', 10, 2)->default(0);
            $table->decimal('location_lat', 10, 8)->nullable();
            $table->decimal('location_lng', 11, 8)->nullable();
            $table->string('deed_path')->nullable();
            $table->integer('lot_count')->default(1);
            $table->integer('boundary_points')->default(4);
            $table->decimal('estimate_price', 15, 2)->default(0);
            $table->decimal('actual_price', 15, 2)->nullable();
            $table->string('status')->default('requested'); // requested, confirmed, advance_paid, site_visit, actual_paid, mapping, completed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('advance_paid_at')->nullable();
            $table->timestamp('final_paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
