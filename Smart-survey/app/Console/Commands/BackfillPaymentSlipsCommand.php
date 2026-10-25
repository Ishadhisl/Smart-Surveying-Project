<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Survey;
use Illuminate\Console\Command;

class BackfillPaymentSlipsCommand extends Command
{
    protected $signature = 'payments:backfill-slips';
    protected $description = 'Backfill legacy surveys.payment_slip_path into payments table rows';

    public function handle(): int
    {
        $surveysWithSlips = Survey::whereNotNull('payment_slip_path')->get();
        $count = 0;

        foreach ($surveysWithSlips as $survey) {
            $existing = Payment::where('survey_id', $survey->id)
                ->where('payment_slip_path', $survey->payment_slip_path)
                ->first();

            if (!$existing) {
                $orderId = 'LEGACY-SLIP-' . $survey->id . '-' . time() . '-' . uniqid();
                $paymentType = $survey->payment_slip_type ?? ($survey->payment_status === 'fully_paid' ? 'balance' : 'advance');

                Payment::create([
                    'survey_id'         => $survey->id,
                    'order_id'          => $orderId,
                    'payment_type'      => $paymentType,
                    'expected_amount'   => (float) ($survey->actual_price ?? $survey->estimate_price),
                    'paid_amount'       => (float) ($survey->actual_price ?? $survey->estimate_price),
                    'currency'          => 'LKR',
                    'payment_method'    => 'bank_transfer',
                    'status'            => 'completed',
                    'payment_slip_path' => $survey->payment_slip_path,
                ]);

                $count++;
            }
        }

        $this->info("Successfully backfilled {$count} legacy payment slips into payments records.");
        return self::SUCCESS;
    }
}
