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
            $table->string('schedule_status')->default('pending')->after('scheduled_at'); // pending, proposed, accepted, declined
            $table->string('payment_status')->default('pending')->after('status'); // pending, advance_paid, fully_paid
            $table->text('cancellation_reason')->nullable()->after('schedule_status');
            $table->decimal('balance_amount', 12, 2)->default(0)->after('actual_price');
            $table->timestamp('proposed_at')->nullable()->after('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surveys', function (Blueprint $table) {
            $table->dropColumn(['schedule_status', 'payment_status', 'cancellation_reason', 'balance_amount', 'proposed_at']);
        });
    }
};
