<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'survey_id',
        'order_id',
        'payment_type', // advance, balance
        'expected_amount',
        'paid_amount',
        'currency',
        'payment_method', // payhere, bank_transfer, cash
        'status', // pending, pending_review, completed, failed, mismatch
        'merchant_id',
        'transaction_id',
        'payhere_payment_id',
        'payment_slip_path',
        'idempotency_key',
        'raw_response',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'raw_response'    => 'array',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
