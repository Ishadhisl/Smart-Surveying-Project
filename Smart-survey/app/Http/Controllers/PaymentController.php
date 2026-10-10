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
        return [
            'merchant_id'     => config('payhere.merchant_id', '1228932'),
            'merchant_secret' => config('payhere.merchant_secret', 'YOUR_SECRET'),
            'sandbox'         => config('payhere.sandbox', true),
        ];
    }

    // ─── Show Payment Page ───────────────────────────────────────────
    public function showPaymentPage(Survey $survey)
    {
        if ($survey->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($survey->status, ['confirmed', 'AdvancePaid', 'ActualPaid', 'completed'])) {
            abort(403, 'Payment is not available at this stage.');
        }

        // Reuse pending Payment record
        $payment = \App\Models\Payment::where('survey_id', $survey->id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return redirect()->route('survey.show', $survey)->with('info', 'No pending payments for this project at the moment.');
        }

        $amountToPay = $payment->amount;
        $paymentType = $payment->type;

        $cfg     = $this->payHereConfig();
        $orderId = 'SS-' . $payment->id . '-' . time();

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

    // ─── PayHere Notify (Server-to-Server) ──────────────────────────
    public function payHereNotify(Request $request)
    {
        $cfg = $this->payHereConfig();

        $merchantId     = $request->merchant_id;
        $orderId        = $request->order_id;
        $payHereAmount  = $request->payhere_amount;
        $payHereCurrency= $request->payhere_currency;
        $statusCode     = $request->status_code;
        $md5sig         = $request->md5sig;

        // Verify hash
        $localMd5sig = strtoupper(md5(
            $merchantId .
            $orderId .
            $payHereAmount .
            $payHereCurrency .
            $statusCode .
            strtoupper(md5($cfg['merchant_secret']))
        ));

        if ($localMd5sig !== $md5sig || $statusCode != 2) {
            return response('Invalid', 400);
        }

        // Parse order id: SS-{survey_id}-{type}-{timestamp}
        $parts = explode('-', $orderId);
        if (count($parts) < 3) return response('Bad order', 400);

        $paymentId   = $parts[1];
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($paymentId) {
            $payment = \App\Models\Payment::where('id', $paymentId)->lockForUpdate()->first();
            if (!$payment || $payment->status !== 'pending') return;

            $survey = $payment->survey;
            if (!$survey) return;

            $payment->update([
                'status' => $payment->type === 'advance' ? 'AdvancePaid' : 'ActualPaid',
                'paid_at' => now(),
                'payhere_order_id' => request('order_id')
            ]);

            if ($payment->type === 'advance') {
                $survey->update([
                    'payment_status'  => 'advance_paid',
                    'advance_paid_at' => now(),
                    'status'          => 'AdvancePaid',
                ]);
                $pricingService = app(\App\Services\PricingService::class);
                $survey->update([
                    'balance_amount' => $pricingService->calculateRemainingBalance($survey)
                ]);
                
                \App\Models\Payment::firstOrCreate([
                    'survey_id' => $survey->id,
                    'type' => 'balance',
                    'status' => 'pending'
                ], [
                    'amount' => $survey->balance_amount
                ]);
            } elseif ($payment->type === 'balance') {
                $survey->update([
                    'payment_status' => 'fully_paid',
                    'final_paid_at'  => now(),
                    'status'         => 'ActualPaid',
                    'balance_amount' => 0,
                ]);
            }
        });

        return response('OK', 200);
    }

    // ─── PayHere Return (After payment — browser redirect) ──────────
    public function payHereReturn(Request $request)
    {
        return redirect()->route('survey.show', $request->order_id ? Survey::find(explode('-', $request->order_id)[1]) : 1)
            ->with('success', 'Payment received! Your project status has been updated.');
    }

    // ─── Bank Transfer Slip Upload ───────────────────────────────────
    public function uploadSlip(Request $request, Survey $survey)
    {
        if ($survey->user_id !== Auth::id()) abort(403);

        $request->validate([
            'payment_slip' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'payment_type' => 'required|in:advance,balance',
        ]);

        $path = $request->file('payment_slip')->store('payment_slips', 'public');

        // Store slip path in survey (we'll add a column)
        $survey->update([
            'payment_slip_path' => $path,
            'payment_slip_type' => $request->payment_type,
            'payment_slip_uploaded_at' => now(),
        ]);

        return back()->with('success', 'Payment slip uploaded! The surveyor will verify and update your status shortly.');
    }
}
