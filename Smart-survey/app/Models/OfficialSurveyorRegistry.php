<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialSurveyorRegistry extends Model
{
    protected $fillable = [
        'license_number',
        'full_name',
        'initials_name',
        'status',
        'district',
        'phone',
        'registered_year',
    ];
}
