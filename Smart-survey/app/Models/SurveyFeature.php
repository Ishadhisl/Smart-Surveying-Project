<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyFeature extends Model
{
    protected $fillable = [
        'survey_id',
        'type',         // building, tree, well, canal, stream, wall, road, other
        'geom',
        'label',
        'clearance_m',
        'source',       // surveyor
        'created_by',
    ];

    protected $casts = [
        'clearance_m' => 'float',
    ];

    /* ---- Relationships ---- */

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---- Helpers ---- */

    /**
     * Get the default clearance for this feature type from config.
     */
    public function defaultClearance(): float
    {
        return config("survey.default_clearances_m.{$this->type}", 0.0);
    }

    /**
     * Feature type options for forms.
     */
    public static function typeOptions(): array
    {
        return [
            'building' => 'Building',
            'tree'     => 'Tree',
            'well'     => 'Well',
            'canal'    => 'Canal',
            'stream'   => 'Stream',
            'wall'     => 'Wall',
            'road'     => 'Road',
            'other'    => 'Other',
        ];
    }
}
