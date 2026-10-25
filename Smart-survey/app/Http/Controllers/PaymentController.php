<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    // ─── PayHere Config (set in .env) ───────────────────────────────
    private function payHereConfig(): array
    {
        $merchantId = config('payhere.merchant_id');
        $merchantSecret = config('payhere.merchant_secret');

        if (empty($merchantSecret)) {
            throw new \RuntimeException('PayHere merchant secret is not configured.');
        }

        return [
            'merchant_id'     => $merchantId,
            'merchant_secret' => $merchantSecret,
            'sandbox'         => config('payhere.sandbox', true),
        ];
    }

    // ─── Show Payment Page ───────────────────────────────────────────
    public function showPaymentPage(Survey $survey)
    {
        $this->authorize('pay', $survey);

        if ($survey->payment_status === 'fully_paid') {
            return redirect()->route('survey.show', $survey)
                ->with('info', 'This project is already fully paid.');
        }

        // Reuse existing pending Payment or determine what is due
        $payment = \App\Models\Payment::where('survey_id', $survey->id)
            ->where('status', 'pending')
            ->first();

        $pricingService = app(\App\Services\PricingService::class);

        if (!$payment) {
            $paymentType = null;
            $amountDue = 0.0;

            if ($survey->payment_status === 'pending' || $survey->status === 'confirmed') {
                $paymentType = 'advance';
                $amountDue = $pricingService->getAdvanceAmount($survey->estimate_price);
            } elseif ($survey->payment_status === 'advance_paid') {
                $paymentType = 'balance';
                $amountDue = $pricingService->calculateRemainingBalance($survey);
            }

            if (!$paymentType || $amountDue <= 0) {
                return redirect()->route('survey.show', $survey)
                    ->with('info', 'No payment is currently due for this project.');
            }

            $cfg = $this->payHereConfig();
            $orderId = 'SS-' . $survey->id . '-' . $paymentType . '-' . time();

            $payment = \App\Models\Payment::create([
                'survey_id'       => $survey->id,
                'order_id'        => $orderId,
                'payment_type'    => $paymentType,
                'expected_amount' => $amountDue,
                'currency'        => 'LKR',
                'payment_method'  => 'payhere',
                'status'          => 'pending',
                'merchant_id'     => $cfg['merchant_id'],
            ]);
        }

        $amountToPay = $payment->expected_amount;
        $paymentType = $payment->payment_type;
        $orderId     = $payment->order_id;

        $cfg = $this->payHereConfig();

        // PayHere hash: MD5(merchant_id + order_id + amount + currency + MD5(secret))
        $hash = strtoupper(md5(
            $cfg['merchant_id'] .
            $orderId .
            number_format($amountToPay, 2, '.', '') .
            'LKR' .
            strtoupper(md5($cfg['merchant_secret']))
        ));

        $payHereUrl = $cfg['sandbox']
            ? 'https://sandbox.payhere.lk/pay/checkout'
            : 'https://www.payhere.lk/pay/checkout';

        return view('client.payment', compact(
            'survey', 'amountToPay', 'paymentType',
            'orderId', 'hash', 'payHereUrl', 'cfg'
        ));
    }

    // ─── PayHere Notify (Server-to-Server Callback) ─────────────────
    public function payHereNotify(Request $request)
    {
        $cfg = $this->payHereConfig();

        $merchantId      = (string) $request->merchant_id;
        $orderId         = (string) $request->order_id;
        $payHereAmount   = (string) $request->payhere_amount;
        $payHereCurrency = (string) $request->payhere_currency;
        $statusCode      = (int) $request->status_code;
        $md5sig          = (string) $request->md5sig;

        // Verify Merchant ID
        if (!hash_equals((string) $cfg['merchant_id'], $merchantId)) {
            return response('Invalid merchant', 400);
        }

        // Verify Currency
        if ($payHereCurrency !== 'LKR') {
            return response('Invalid currency', 400);
        }

        // Verify Signature Hash
        $localMd5sig = strtoupper(md5(
            $merchantId .
            $orderId .
            $payHereAmount .
            $payHereCurrency .
            $statusCode .
            strtoupper(md5($cfg['merchant_secret']))
        ));

        if (!hash_equals($localMd5sig, $md5sig)) {
            return response('Invalid signature', 400);
        }

        // Process inside DB transaction with pessimistic locking
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $orderId, $statusCode, $payHereAmount) {
            $payment = \App\Models\Payment::where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return response('Payment record not found', 404);
            }

            // Idempotency: if already completed, acknowledge immediately
            if ($payment->status === 'completed') {
                return response('OK', 200);
            }

            $payment->raw_response = $request->all();
            $payment->payhere_payment_id = $request->payment_id;

            if ($statusCode !== 2) {
                $payment->status = 'failed';
                $payment->save();
                return response('Payment not successful', 200);
            }

            // Check amount mismatch
            $expectedFormatted = number_format($payment->expected_amount, 2, '.', '');
            $receivedFormatted = number_format((float) $payHereAmount, 2, '.', '');

            if (!hash_equals($expectedFormatted, $receivedFormatted)) {
                $payment->status = 'mismatch';
                $payment->paid_amount = (float) $payHereAmount;
                $payment->save();
                return response('Amount mismatch', 400);
            }

            $payment->status = 'completed';
            $payment->paid_amount = (float) $payHereAmount;
            $payment->save();

            $survey = $payment->survey;
            $transitionService = app(\App\Services\SurveyTransitionService::class);
            $pricingService = app(\App\Services\PricingService::class);

            if ($payment->payment_type === 'advance') {
                $survey->payment_status = 'advance_paid';
                $survey->advance_paid_at = now();
                $survey->balance_amount = $pricingService->calculateRemainingBalance($survey);
                $survey->save();

                $transitionService->transition($survey, \App\Enums\SurveyStatus::ADVANCE_PAID);
            } elseif ($payment->payment_type === 'balance') {
                $survey->payment_status = 'fully_paid';
                $survey->final_paid_at = now();
                $survey->balance_amount = 0.0;
                $survey->save();

                $transitionService->transition($survey, \App\Enums\SurveyStatus::ACTUAL_PAID);
            }

            return response('OK', 200);
        });
    }

    // ─── PayHere Return (Browser Redirect after payment) ─────────────
    public function payHereReturn(Request $request)
    {
        $orderId = $request->order_id;
        $payment = null;

        if ($orderId) {
            $payment = \App\Models\Payment::where('order_id', $orderId)->first();
        }

        if (!$payment) {
            return redirect()->route('survey.index')
                ->with('warning', 'Payment processing. Please check your project status.');
        }

        $survey = $payment->survey;
        if ($survey->user_id !== Auth::id() && !Auth::user()->isSurveyor()) {
            abort(403);
        }

        return redirect()->route('survey.show', $survey)
            ->with('success', 'Payment status updated successfully.');
    }

    // ─── Bank Transfer Slip Upload ───────────────────────────────────
    public function uploadSlip(Request $request, Survey $survey)
    {
        $this->authorize('pay', $survey);

        $validated = $request->validate([
            'payment_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_type' => 'required|in:advance,balance',
        ]);

        // Store privately
        $path = $request->file('payment_slip')->store('payment_slips', 'private');

        $pricingService = app(\App\Services\PricingService::class);
        $expectedAmount = $validated['payment_type'] === 'advance'
            ? $pricingService->getAdvanceAmount($survey->estimate_price)
            : $pricingService->calculateRemainingBalance($survey);

        $orderId = 'SLIP-' . $survey->id . '-' . $validated['payment_type'] . '-' . time();

        \App\Models\Payment::create([
            'survey_id'         => $survey->id,
            'order_id'          => $orderId,
            'payment_type'      => $validated['payment_type'],
            'expected_amount'   => $expectedAmount,
            'currency'          => 'LKR',
            'payment_method'    => 'bank_transfer',
            'status'            => 'pending_review',
            'payment_slip_path' => $path,
        ]);

        return back()->with('success', 'Payment slip uploaded for verification. The surveyor will review it shortly.');
    }
}
