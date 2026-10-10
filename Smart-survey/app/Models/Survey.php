<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Survey extends Model
{
    /** @use HasFactory<\Database\Factories\SurveyFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'village',
        'purpose',
        'other_purpose',
        'size_acres',
        'size_perches',
        'location_lat',
        'location_lng',
        'deed_path',
        'lot_count',
        'boundary_points',
        'estimate_price',
        'actual_price',
        'balance_amount',
        'status',
        'schedule_status',
        'payment_status',
        'scheduled_at',
        'proposed_at',
        'cancellation_reason',
        'advance_paid_at',
        'final_paid_at',
        'payment_slip_path',
        'payment_slip_type',
        'payment_slip_uploaded_at',
        'boundary_polygon',
        'computed_area_sqm',
        'computed_perimeter_m',
    ];

    protected $casts = [
        'scheduled_at'             => 'datetime',
        'proposed_at'              => 'datetime',
        'advance_paid_at'          => 'datetime',
        'final_paid_at'            => 'datetime',
        'payment_slip_uploaded_at' => 'datetime',
        'size_acres'               => 'float',
        'size_perches'             => 'float',
        'estimate_price'           => 'float',
        'actual_price'             => 'float',
        'balance_amount'           => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function boundaryPoints()
    {
        return $this->hasMany(BoundaryPoint::class)->orderBy('point_order');
    }
}
