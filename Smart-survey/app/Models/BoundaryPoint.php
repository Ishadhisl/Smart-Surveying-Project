<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoundaryPoint extends Model
{
    use HasFactory;

    protected $fillable = ['survey_id', 'latitude', 'longitude', 'point_order'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
