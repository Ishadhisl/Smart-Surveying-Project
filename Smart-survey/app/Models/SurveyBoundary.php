<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyBoundary extends Model
{
    protected $fillable = [
        'survey_id',
        'kind',        // client_sketch, surveyor_final
        'version_no',
        'geom',
        'area_sqm',
        'perimeter_m',
        'vertex_count',
        'source',      // drawn, csv, geojson
        'note',
        'created_by',
    ];

    protected $casts = [
        'area_sqm'     => 'float',
        'perimeter_m'  => 'float',
        'vertex_count' => 'integer',
        'version_no'   => 'integer',
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
     * Get area in perches (1 perch = 25.29285264 sqm).
     */
    public function areaInPerches(): float
    {
        return $this->area_sqm / config('survey.perch_in_sqm', 25.29285264);
    }

    /**
     * Get formatted acres + perches string.
     */
    public function formattedArea(): string
    {
        $totalPerches = $this->areaInPerches();
        $acres = floor($totalPerches / 160);
        $perches = fmod($totalPerches, 160);

        if ($acres > 0) {
            return sprintf('%d Acres, %.2f Perches', $acres, $perches);
        }
        return sprintf('%.2f Perches', $perches);
    }
}
