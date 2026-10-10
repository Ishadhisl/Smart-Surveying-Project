<?php

namespace App\Services;

use App\Models\Survey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SurveyService
{
    /**
     * Get surveys for a specific user.
     */
    public function getUserSurveys()
    {
        return Auth::user()->surveys()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get upcoming site visits for today and tomorrow.
     */
    public function getUpcomingVisits()
    {
        return Survey::whereIn('status', ['confirmed', 'advance_paid'])
            ->whereBetween('scheduled_at', [
                Carbon::today()->startOfDay(),
                Carbon::today()->addDays(7)->endOfDay()
            ])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get surveys where the client has declined the proposed schedule.
     */
    public function getDeclinedVisits()
    {
        return Survey::with('user')->where('schedule_status', 'declined')->get();
    }

    /**
     * Get dashboard statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => Survey::count(),
            'completed' => Survey::where('status', 'completed')->count(),
            'pending' => Survey::whereNotIn('status', ['completed'])->count(),
            'revenue' => Survey::where('status', 'completed')->sum('actual_price'),
            'monthly_revenue' => Survey::where('status', 'completed')
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->sum('actual_price'),
        ];
    }
}
