<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    /**
     * Determine whether the user can view the survey.
     */
    public function view(User $user, Survey $survey): bool
    {
        return $user->isSurveyor() || $user->id === $survey->user_id;
    }

    /**
     * Determine whether the user can respond to a schedule proposal.
     */
    public function respondSchedule(User $user, Survey $survey): bool
    {
        return $user->id === $survey->user_id && $survey->status === 'requested';
    }

    /**
     * Determine whether the user can make a payment.
     */
    public function pay(User $user, Survey $survey): bool
    {
        return $user->id === $survey->user_id && in_array($survey->status, [
            'confirmed',
            'advance_paid',
            'site_visit',
            'actual_paid',
        ], true);
    }

    /**
     * Determine whether the user can cancel the survey.
     */
    public function cancel(User $user, Survey $survey): bool
    {
        if ($user->isSurveyor()) {
            return !in_array($survey->status, ['completed', 'cancelled'], true);
        }

        // Client can only cancel before advance is paid
        return $user->id === $survey->user_id && in_array($survey->status, ['requested', 'confirmed'], true);
    }

    /**
     * Determine whether the user can store/update boundary points.
     */
    public function updateBoundary(User $user, Survey $survey, string $kind = 'client_sketch'): bool
    {
        if ($kind === 'surveyor_final') {
            return $user->isSurveyor() && $survey->status !== 'completed';
        }

        // client_sketch can only be modified by client owner before advance is paid
        return $user->id === $survey->user_id && in_array($survey->status, ['requested', 'confirmed'], true);
    }

    /**
     * Determine whether the user can manage features.
     */
    public function manageFeatures(User $user, Survey $survey): bool
    {
        return $user->isSurveyor() && $survey->status !== 'completed';
    }

    /**
     * Determine whether the user can manage subdivision lots.
     */
    public function manageSubdivision(User $user, Survey $survey): bool
    {
        return $user->isSurveyor() && $survey->status !== 'completed';
    }

    /**
     * Determine whether the user can download private documents.
     */
    public function downloadDocument(User $user, Survey $survey): bool
    {
        return $user->isSurveyor() || $user->id === $survey->user_id;
    }
}
