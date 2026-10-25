<?php

namespace Tests\Unit;

use App\Enums\SurveyStatus;
use App\Models\Survey;
use App\Models\User;
use App\Services\SurveyTransitionService;
use InvalidArgumentException;
use Tests\TestCase;

class SurveyTransitionTest extends TestCase
{
    private SurveyTransitionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SurveyTransitionService();
    }

    public function test_valid_status_transition_sequence(): void
    {
        $survey = new Survey([
            'status' => SurveyStatus::REQUESTED->value,
            'payment_status' => 'pending',
        ]);
        $survey->user_id = 1;

        $client = new User(['role' => 'client']);
        $client->id = 1;
        $surveyor = new User(['role' => 'admin']);
        $surveyor->id = 2;

        // requested -> confirmed by client owner
        $this->service->transition($survey, SurveyStatus::CONFIRMED, $client);
        $this->assertEquals(SurveyStatus::CONFIRMED->value, $survey->status);

        // confirmed -> advance_paid
        $this->service->transition($survey, SurveyStatus::ADVANCE_PAID);
        $this->assertEquals(SurveyStatus::ADVANCE_PAID->value, $survey->status);

        // advance_paid -> site_visit
        $this->service->transition($survey, SurveyStatus::SITE_VISIT);
        $this->assertEquals(SurveyStatus::SITE_VISIT->value, $survey->status);

        // site_visit -> actual_paid
        $this->service->transition($survey, SurveyStatus::ACTUAL_PAID);
        $this->assertEquals(SurveyStatus::ACTUAL_PAID->value, $survey->status);

        // actual_paid -> mapping requires payment_status = fully_paid
        $survey->payment_status = 'fully_paid';
        $this->service->transition($survey, SurveyStatus::MAPPING);
        $this->assertEquals(SurveyStatus::MAPPING->value, $survey->status);

        // mapping -> completed
        $this->service->transition($survey, SurveyStatus::COMPLETED);
        $this->assertEquals(SurveyStatus::COMPLETED->value, $survey->status);
    }

    public function test_invalid_status_transition_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $survey = new Survey([
            'status' => SurveyStatus::REQUESTED->value,
            'payment_status' => 'pending',
            'user_id' => 1,
        ]);

        // Jumping directly from requested to mapping is invalid
        $this->service->transition($survey, SurveyStatus::MAPPING);
    }

    public function test_client_cannot_cancel_after_advance_paid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $survey = new Survey([
            'status' => SurveyStatus::ADVANCE_PAID->value,
            'payment_status' => 'advance_paid',
            'user_id' => 1,
        ]);

        $client = new User(['id' => 1, 'role' => 'client']);

        // Client attempting to cancel after advance payment should fail
        $this->service->transition($survey, SurveyStatus::CANCELLED, $client);
    }

    public function test_surveyor_can_cancel_after_advance_paid_with_audit_trail(): void
    {
        $survey = new Survey([
            'status' => SurveyStatus::ADVANCE_PAID->value,
            'payment_status' => 'advance_paid',
            'user_id' => 1,
        ]);

        $surveyor = new User(['id' => 2, 'role' => 'admin']);

        $this->service->transition($survey, SurveyStatus::CANCELLED, $surveyor, [
            'cancellation_reason' => 'Client requested cancellation due to weather disruption.',
            'refund_due' => true,
        ]);

        $this->assertEquals(SurveyStatus::CANCELLED->value, $survey->status);
        $this->assertEquals('Client requested cancellation due to weather disruption.', $survey->cancellation_reason);
        $this->assertTrue($survey->refund_due);
    }
}
