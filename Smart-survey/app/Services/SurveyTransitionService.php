<?php

namespace App\Services;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\User;
use InvalidArgumentException;

class SurveyTransitionService
{
    /**
     * Transition a survey to a target status with validation and role guards.
     */
    public function transition(Survey $survey, string|SurveyStatus $targetStatus, ?User $actor = null, array $context = []): Survey
    {
        $targetValue = $targetStatus instanceof SurveyStatus ? $targetStatus->value : $targetStatus;
        $currentStatus = SurveyStatus::tryFrom($survey->status);

        if (!$currentStatus) {
            throw new InvalidArgumentException("Current survey status [{$survey->status}] is invalid.");
        }

        if (!$currentStatus->canTransitionTo($targetValue)) {
            throw new InvalidArgumentException("Invalid status transition from [{$survey->status}] to [{$targetValue}].");
        }

        // Role-based transition guard
        if ($actor) {
            if ($targetValue === SurveyStatus::CONFIRMED->value) {
                // Client responds to schedule
                if ($actor->role !== 'client' || $actor->id !== $survey->user_id) {
                    throw new InvalidArgumentException("Only the survey owner client can confirm a proposed schedule.");
                }
            }

            if ($targetValue === SurveyStatus::CANCELLED->value) {
                // Pre-advance: client or surveyor can cancel
                // Post-advance: only surveyor can cancel
                $hasPaidAdvance = in_array($survey->status, [
                    SurveyStatus::ADVANCE_PAID->value,
                    SurveyStatus::SITE_VISIT->value,
                    SurveyStatus::ACTUAL_PAID->value,
                    SurveyStatus::MAPPING->value,
                ], true);

                if ($hasPaidAdvance && $actor->role !== 'admin') {
                    throw new InvalidArgumentException("Only the licensed surveyor can cancel a survey once an advance has been paid.");
                }
            }
        }

        // Additional business rule checks
        if ($targetValue === SurveyStatus::MAPPING->value) {
            if ($survey->payment_status !== 'fully_paid') {
                throw new InvalidArgumentException("Cannot transition to mapping until survey is fully paid.");
            }
        }

        if ($targetValue === SurveyStatus::CANCELLED->value) {
            if (isset($context['cancellation_reason'])) {
                $survey->cancellation_reason = $context['cancellation_reason'];
            }
            if (isset($context['refund_due'])) {
                $survey->refund_due = (bool)$context['refund_due'];
            }
        }

        $survey->status = $targetValue;
        if ($survey->exists) {
            $survey->save();
        }

        return $survey;
    }
}
