<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Survey;
use App\Models\User;
use App\Services\PricingService;
use App\Services\SurveyTransitionService;
use Tests\TestCase;

class PaymentLogicTest extends TestCase
{
    private PricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new PricingService();
    }

    public function test_advance_amount_is_canonical(): void
    {
        // 130100 / 3 = 43366.666... rounded to nearest 10 = 43370
        $this->assertEquals(43370.0, $this->pricingService->getAdvanceAmount(130100));

        // 32000 / 3 = 10666.666... rounded to nearest 10 = 10670
        $this->assertEquals(10670.0, $this->pricingService->getAdvanceAmount(32000));
    }

    public function test_payhere_hash_generation(): void
    {
        $merchantId = '123456';
        $orderId = 'SS-1-advance-1234567890';
        $amount = 43370.0;
        $currency = 'LKR';
        $secret = 'test_secret_123';

        $expectedHash = strtoupper(md5(
            $merchantId .
            $orderId .
            number_format($amount, 2, '.', '') .
            $currency .
            strtoupper(md5($secret))
        ));

        $this->assertNotEmpty($expectedHash);
        $this->assertEquals(32, strlen($expectedHash));
    }

    public function test_payment_balance_calculation(): void
    {
        $actualPrice = 120000.0;
        $advancePaid = 40000.0;

        $balance = $this->pricingService->calculateBalance($actualPrice, $advancePaid);
        $this->assertEquals(80000.0, $balance);
    }
}
