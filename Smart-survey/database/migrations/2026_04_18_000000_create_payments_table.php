<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->restrictOnDelete();
            $table->string('order_id', 100)->unique();
            $table->string('payment_type', 20); // advance, balance
            $table->decimal('expected_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('LKR');
            $table->string('payment_method', 20)->default('payhere'); // payhere, bank_transfer, cash
            $table->string('status', 20)->default('pending'); // pending, pending_review, completed, failed, mismatch
            $table->string('merchant_id', 100)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('payhere_payment_id', 100)->nullable();
            $table->string('payment_slip_path', 255)->nullable();
            $table->string('idempotency_key', 100)->unique()->nullable();
            $table->jsonb('raw_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
