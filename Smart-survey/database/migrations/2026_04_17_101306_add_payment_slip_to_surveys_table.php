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
        Schema::table('surveys', function (Blueprint $table) {
            $table->string('payment_slip_path')->nullable()->after('deed_path');
            $table->string('payment_slip_type')->nullable()->after('payment_slip_path');   // advance | balance
            $table->timestamp('payment_slip_uploaded_at')->nullable()->after('payment_slip_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            //
        });
    }
};
