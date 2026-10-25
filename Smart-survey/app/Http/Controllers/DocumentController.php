<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Stream authenticated private documents (deeds or slips).
     */
    public function download(Request $request, Survey $survey, string $type): StreamedResponse
    {
        $this->authorize('downloadDocument', $survey);

        $path = null;

        if ($type === 'deed') {
            $path = $survey->deed_path;
        } elseif ($type === 'slip') {
            $paymentId = $request->query('payment_id');
            if ($paymentId) {
                $payment = Payment::where('survey_id', $survey->id)->where('id', $paymentId)->first();
                $path = $payment?->payment_slip_path;
            } else {
                $path = $survey->payment_slip_path;
            }
        }

        if (!$path) {
            abort(404, 'Requested document not found.');
        }

        // Check private disk first, fallback to public disk for legacy unmigrated files
        $disk = Storage::disk('private')->exists($path) ? 'private' : (Storage::disk('public')->exists($path) ? 'public' : null);

        if (!$disk) {
            abort(404, 'Document file not found on disk.');
        }

        return Storage::disk($disk)->download($path);
    }
}
