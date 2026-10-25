<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyLot extends Model
{
    protected $fillable = [
        'survey_id',
        'boundary_id',
        'proposal_no',
        'lot_no',
        'geom',
        'type',        // residential, access_road, reserve
        'area_sqm',
        'status',      // proposed, final
        'created_by',
    ];

    protected $casts = [
        'proposal_no' => 'integer',
        'lot_no'      => 'integer',
        'area_sqm'    => 'float',
    ];

    /* ---- Relationships ---- */

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function boundary()
    {
        return $this->belongsTo(SurveyBoundary::class, 'boundary_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /* ---- Helpers ---- */

    /**
     * Get area in perches.
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

    /**
     * Get A-R-P format string (1 Acre = 4 Roods, 1 Rood = 40 Perches).
     */
    public function formattedArp(): string
    {
        $totalPerches = $this->areaInPerches();
        $acres = floor($totalPerches / 160);
        $remainder = fmod($totalPerches, 160);
        $roods = floor($remainder / 40);
        $perches = fmod($remainder, 40);

        return sprintf('%dA %dR %.2fP', $acres, $roods, $perches);
    }
}
