<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\User;
use App\Mail\FeedbackRequestMail;
use App\Services\PricingService;
use App\Services\SurveyService;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SurveyController extends Controller
{
    protected $surveyService;
    protected $pricingService;
    protected $weatherService;

    public function __construct(SurveyService $surveyService, PricingService $pricingService, WeatherService $weatherService)
    {
        $this->surveyService = $surveyService;
        $this->pricingService = $pricingService;
        $this->weatherService = $weatherService;
    }

    public function index()
    {
        $surveys = $this->surveyService->getUserSurveys();
        return view('client.index', compact('surveys'));
    }

    public function create()
    {
        return view('client.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'village' => 'required|string',
            'purpose' => 'required|string',
            'other_purpose' => 'nullable|string',
            'size_acres' => 'nullable|numeric|min:0',
            'size_perches' => 'nullable|numeric|min:0',
            'lot_count' => 'nullable|integer|min:1',
            'boundary_points' => 'nullable|integer|min:4',
            'location_lat' => 'nullable|numeric',
            'location_lng' => 'nullable|numeric',
            'deed' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $deedPath = null;
        if ($request->hasFile('deed')) {
            $deedPath = $request->file('deed')->store('deeds', 'private');
        }

        // Price Calculation via Service
        $estimatePrice = $this->pricingService->calculateEstimate($validated);

        $survey = Survey::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'deed_path' => $deedPath,
            'estimate_price' => $estimatePrice,
            'status' => 'requested',
        ]));

        return redirect()->route('survey.index')->with('success', 'Your request has been submitted successfully.');
    }

    public function show(Survey $survey)
    {
        if ($survey->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        $weather = null;
        $targetDate = $survey->scheduled_at ?? $survey->proposed_at;
        if ($targetDate && $survey->location_lat && $survey->location_lng) {
            $weather = $this->weatherService->getWeatherForDate(
                (float) $survey->location_lat,
                (float) $survey->location_lng,
                $targetDate
            );
        }

        return view('client.show', compact('survey', 'weather'));
    }

    public function adminDashboard()
    {
        $stats = $this->surveyService->getStats();
        $recentProjects = Survey::with('user')->orderBy('created_at', 'desc')->take(10)->get();
        
        // Reminder for Site Visits
        $upcomingVisits = $this->surveyService->getUpcomingVisits();
        $declinedVisits = $this->surveyService->getDeclinedVisits();

        // Attach weather data to upcoming visits
        foreach ($upcomingVisits as $visit) {
            if ($visit->location_lat && $visit->location_lng) {
                $visit->weather = $this->weatherService->getWeather($visit->location_lat, $visit->location_lng, $visit->scheduled_at);
            }
        }

        return view('admin.dashboard', compact('stats', 'recentProjects', 'upcomingVisits', 'declinedVisits'));
    }

    public function adminProjects(Request $request)
    {
        $query = Survey::with('user');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('village', 'ilike', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match($request->input('sort', 'newest')) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'status' => $query->orderBy('status'),
            default  => $query->orderBy('created_at', 'desc'),
        };

        $projects = $query->paginate(15);
        return view('admin.projects', compact('projects'));
    }

    public function adminUpcomingVisits()
    {
        $upcomingVisits = $this->surveyService->getUpcomingVisits();

        // Attach weather data
        foreach ($upcomingVisits as $visit) {
            if ($visit->location_lat && $visit->location_lng) {
                $visit->weather = $this->weatherService->getWeather($visit->location_lat, $visit->location_lng, $visit->scheduled_at);
            }
        }
        
        return view('admin.upcoming-visits', compact('upcomingVisits'));
    }

    public function updateStatus(Request $request, Survey $survey)
    {
        $validated = $request->validate([
            'status'       => 'required|string',
            'actual_price' => 'nullable|numeric|min:0',
            'proposed_at'  => 'nullable|date',
        ]);

        $updateData = ['status' => $validated['status']];

        if (!empty($validated['actual_price'])) {
            $updateData['actual_price']   = $validated['actual_price'];
            $updateData['balance_amount'] = $this->pricingService->calculateRemainingBalance($survey, $validated['actual_price']);
        }

        if (!empty($validated['proposed_at'])) {
            $updateData['proposed_at']     = $validated['proposed_at'];
            $updateData['schedule_status'] = 'proposed';
        }

        $survey->update($updateData);

        // Send feedback request email if status is completed
        if ($validated['status'] === 'completed') {
            try {
                Mail::to($survey->user->email)->send(new FeedbackRequestMail($survey));
            } catch (\Exception $e) {
                // Log error but don't break the response
                \Illuminate\Support\Facades\Log::error('Feedback email failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Project #' . $survey->id . ' updated successfully.');
    }

    public function cancel(Request $request, Survey $survey)
    {
        $this->authorize('cancel', $survey);

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $transitionService = app(\App\Services\SurveyTransitionService::class);
        $context = [
            'cancellation_reason' => $validated['cancellation_reason'] ?? 'Cancelled by user',
            'refund_due' => in_array($survey->status, ['advance_paid', 'site_visit', 'actual_paid', 'mapping'], true),
        ];

        $transitionService->transition($survey, \App\Enums\SurveyStatus::CANCELLED, Auth::user(), $context);

        return back()->with('success', 'Survey #' . $survey->id . ' has been cancelled.');
    }

    public function proposeSchedule(Request $request, Survey $survey)
    {
        $request->validate(['proposed_at' => 'required|date']);

        $survey->update([
            'proposed_at' => $request->proposed_at,
            'schedule_status' => 'proposed',
            'status' => 'requested'
        ]);

        return back()->with('success', 'Schedule proposed to client.');
    }

    public function respondToSchedule(Request $request, Survey $survey)
    {
        $request->validate(['response' => 'required|in:accept,decline']);

        if ($request->response === 'accept') {
            $survey->update([
                'scheduled_at'    => $survey->proposed_at,
                'schedule_status' => 'accepted',
            ]);

            $transitionService = app(\App\Services\SurveyTransitionService::class);
            $transitionService->transition($survey, \App\Enums\SurveyStatus::CONFIRMED, Auth::user());
            
            $pricingService = app(\App\Services\PricingService::class);
            $advanceAmount = $pricingService->getAdvanceAmount($survey->estimate_price);
            $orderId = 'SS-' . $survey->id . '-advance-' . time();

            // Generate pending Advance payment record if none exists
            $existing = \App\Models\Payment::where('survey_id', $survey->id)
                ->where('payment_type', 'advance')
                ->whereIn('status', ['pending', 'completed'])
                ->first();

            if (!$existing) {
                \App\Models\Payment::create([
                    'survey_id'       => $survey->id,
                    'order_id'        => $orderId,
                    'payment_type'    => 'advance',
                    'expected_amount' => $advanceAmount,
                    'currency'        => 'LKR',
                    'payment_method'  => 'payhere',
                    'status'          => 'pending',
                    'merchant_id'     => config('payhere.merchant_id'),
                ]);
            }

            return back()->with('success', 'Schedule accepted. Project is now confirmed.');
        } else {
            $survey->update([
                'schedule_status' => 'declined',
                'proposed_at'     => null,
            ]);
            return back()->with('warning', 'Schedule declined. Surveyor will propose a new time.');
        }
    }

    public function markPayment(Request $request, Survey $survey)
    {
        $request->validate(['type' => 'required|in:advance,full']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $survey) {
            $paymentType = $request->type === 'advance' ? 'advance' : 'balance';
            $payment = \App\Models\Payment::where('survey_id', $survey->id)
                ->where('payment_type', $paymentType)
                ->lockForUpdate()
                ->first();

            $pricingService = app(\App\Services\PricingService::class);
            $transitionService = app(\App\Services\SurveyTransitionService::class);

            if ($request->type === 'advance') {
                $advanceAmount = $pricingService->getAdvanceAmount($survey->estimate_price);
                if (!$payment) {
                    $orderId = 'MANUAL-' . $survey->id . '-advance-' . time();
                    $payment = \App\Models\Payment::create([
                        'survey_id'       => $survey->id,
                        'order_id'        => $orderId,
                        'payment_type'    => 'advance',
                        'expected_amount' => $advanceAmount,
                        'paid_amount'     => $advanceAmount,
                        'currency'        => 'LKR',
                        'payment_method'  => 'cash',
                        'status'          => 'completed',
                    ]);
                } else {
                    $payment->update([
                        'status'      => 'completed',
                        'paid_amount' => $payment->expected_amount,
                    ]);
                }

                $survey->update([
                    'payment_status'  => 'advance_paid',
                    'advance_paid_at' => now(),
                    'balance_amount'  => $pricingService->calculateRemainingBalance($survey),
                ]);

                $transitionService->transition($survey, \App\Enums\SurveyStatus::ADVANCE_PAID);

                // Pre-generate pending balance payment record
                $balanceOrderId = 'SS-' . $survey->id . '-balance-' . time();
                \App\Models\Payment::firstOrCreate([
                    'survey_id'    => $survey->id,
                    'payment_type' => 'balance',
                    'status'       => 'pending',
                ], [
                    'order_id'        => $balanceOrderId,
                    'expected_amount' => $survey->balance_amount,
                    'currency'        => 'LKR',
                    'payment_method'  => 'payhere',
                    'merchant_id'     => config('payhere.merchant_id'),
                ]);
            } else {
                $balanceAmount = $pricingService->calculateRemainingBalance($survey);
                if (!$payment) {
                    $orderId = 'MANUAL-' . $survey->id . '-balance-' . time();
                    $payment = \App\Models\Payment::create([
                        'survey_id'       => $survey->id,
                        'order_id'        => $orderId,
                        'payment_type'    => 'balance',
                        'expected_amount' => $balanceAmount,
                        'paid_amount'     => $balanceAmount,
                        'currency'        => 'LKR',
                        'payment_method'  => 'cash',
                        'status'          => 'completed',
                    ]);
                } else {
                    $payment->update([
                        'status'      => 'completed',
                        'paid_amount' => $payment->expected_amount,
                    ]);
                }

                $survey->update([
                    'payment_status' => 'fully_paid',
                    'final_paid_at'  => now(),
                    'balance_amount' => 0.0,
                ]);

                $transitionService->transition($survey, \App\Enums\SurveyStatus::ACTUAL_PAID);
            }
        });

        return back()->with('success', 'Payment marked successfully.');
    }

    public function adminClients(Request $request)
    {
        $search = $request->search;
        $clients = User::where('role', 'client')
            ->with('surveys')
            ->when($search, function($q) use ($search) {
                $q->where(function($sub) use ($search) {
                    $sub->where('name', 'ilike', "%{$search}%")
                        ->orWhere('whatsapp_number', 'ilike', "%{$search}%");
                });
            })
            ->withCount('surveys')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $pendingSurveyors = User::where('role', 'admin')
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.clients', compact('clients', 'pendingSurveyors'));
    }

    public function approveSurveyor(User $user)
    {
        $user->update(['is_approved' => true]);
        return back()->with('success', "Surveyor {$user->name} (License: {$user->license_number}) has been approved successfully.");
    }

    public function rejectSurveyor(User $user)
    {
        $userName = $user->name;
        $user->delete();
        return back()->with('success', "Surveyor application for {$userName} has been rejected.");
    }

    public function adminDeeds(Request $request)
    {
        $search = $request->search;
        $surveysWithDeeds = Survey::whereNotNull('deed_path')
            ->with('user')
            ->when($search, function($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('user', function($uq) use ($search) {
                        $uq->where('name', 'ilike', "%{$search}%");
                    })->orWhere('village', 'ilike', "%{$search}%");
                    
                    if (is_numeric($search)) {
                        $sub->orWhere('id', $search);
                    }
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('admin.deeds', compact('surveysWithDeeds'));
    }

    public function downloadInvoice(Survey $survey)
    {
        // Ensure the correct user is generating their own invoice, unless admin
        if ($survey->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
            abort(403);
        }

        // Only allow invoice generation if fully paid
        if ($survey->payment_status !== 'fully_paid') {
            abort(403, 'Invoice is only available for fully paid projects.');
        }

        return view('client.invoice', compact('survey'));
    }

    public function checkWeather(Request $request)
    {
        $lat = (float) $request->lat;
        $lng = (float) $request->lng;
        $date = $request->date;

        if (!$lat || !$lng) {
            return response()->json(['error' => 'Coordinates missing'], 400);
        }

        if ($date) {
            $weather = $this->weatherService->getWeatherForDate($lat, $lng, $date);
            return response()->json($weather);
        }

        $weather = $this->weatherService->getWeather($lat, $lng);
        if ($weather) {
            return response()->json($weather);
        }

        return response()->json(['error' => 'Could not fetch weather'], 500);
    }

    public function weatherForecast(Request $request, Survey $survey)
    {
        $lat = (float) ($survey->location_lat ?? $request->lat ?? 7.2906);
        $lng = (float) ($survey->location_lng ?? $request->lng ?? 80.6337);

        $forecast = $this->weatherService->getDailyForecast($lat, $lng);

        if (!empty($forecast['error'])) {
            return response()->json([
                'error' => true,
                'message' => $forecast['message'] ?? 'Could not fetch weather forecast',
            ], 503);
        }

        if (!empty($forecast['per_day'])) {
            foreach ($forecast['per_day'] as &$day) {
                $code = $day['weather_code'] ?? 0;
                $day['icon'] = $this->weatherService->getWeatherIcon($code);
                $day['description'] = $this->weatherService->getWeatherDescription($code);
                $day['is_recommended'] = in_array($day['date'], $forecast['best_days_in_order'] ?? [], true);
            }
        }

        $forecast['disclaimer'] = 'Planning defaults, not legal regulations. Licensed surveyor makes the final visit decision.';
        return response()->json($forecast);
    }
}
