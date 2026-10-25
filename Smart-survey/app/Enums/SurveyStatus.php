<?php

namespace App\Enums;

enum SurveyStatus: string
{
    case REQUESTED = 'requested';
    case CONFIRMED = 'confirmed';
    case ADVANCE_PAID = 'advance_paid';
    case SITE_VISIT = 'site_visit';
    case ACTUAL_PAID = 'actual_paid';
    case MAPPING = 'mapping';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * Define valid state transitions.
     *
     * @return array<string, list<string>>
     */
    public static function transitions(): array
    {
        return [
            self::REQUESTED->value => [
                self::CONFIRMED->value,
                self::CANCELLED->value,
            ],
            self::CONFIRMED->value => [
                self::ADVANCE_PAID->value,
                self::CANCELLED->value,
            ],
            self::ADVANCE_PAID->value => [
                self::SITE_VISIT->value,
                self::ACTUAL_PAID->value, // In case balance is cleared right after visit
                self::CANCELLED->value,   // Only surveyor may cancel after advance
            ],
            self::SITE_VISIT->value => [
                self::ACTUAL_PAID->value,
                self::CANCELLED->value,
            ],
            self::ACTUAL_PAID->value => [
                self::MAPPING->value,     // Requires payment_status = fully_paid
                self::CANCELLED->value,
            ],
            self::MAPPING->value => [
                self::COMPLETED->value,
            ],
            self::COMPLETED->value => [],
            self::CANCELLED->value => [],
        ];
    }

    public function canTransitionTo(string $targetStatus): bool
    {
        $allowed = self::transitions()[$this->value] ?? [];
        return in_array($targetStatus, $allowed, true);
    }
}
