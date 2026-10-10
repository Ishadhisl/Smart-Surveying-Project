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
            $deedPath = $request->file('deed')->store('deeds', 'public');
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
        return view('client.show', compact('survey'));
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
                'scheduled_at' => $survey->proposed_at,
                'schedule_status' => 'accepted',
                'status' => 'confirmed'
            ]);
            
            $pricingService = app(\App\Services\PricingService::class);
            // Generate pending Advance payment
            \App\Models\Payment::firstOrCreate([
                'survey_id' => $survey->id,
                'type' => 'advance',
                'status' => 'pending'
            ], [
                'amount' => $pricingService->getAdvanceAmount($survey->estimate_price)
            ]);

            return back()->with('success', 'Schedule accepted. Project is now confirmed.');
        } else {
            $survey->update([
                'schedule_status' => 'declined',
                'proposed_at' => null
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
                ->where('type', $paymentType)
                ->lockForUpdate()
                ->first();
                
            if ($payment) {
                $payment->update([
                    'status' => $request->type === 'advance' ? 'AdvancePaid' : 'ActualPaid',
                    'paid_at' => now()
                ]);
            }

            if ($request->type === 'advance') {
                $survey->update([
                    'payment_status'  => 'advance_paid',
                    'advance_paid_at' => now(),
                    'status'          => 'AdvancePaid'
                ]);
                $survey->update([
                    'balance_amount' => $this->pricingService->calculateRemainingBalance($survey)
                ]);
                
                // Create pending balance payment
                \App\Models\Payment::firstOrCreate([
                    'survey_id' => $survey->id,
                    'type' => 'balance',
                    'status' => 'pending'
                ], [
                    'amount' => $survey->balance_amount
                ]);
            } else {
                $survey->update([
                    'payment_status' => 'fully_paid',
                    'final_paid_at' => now(),
                    'status' => 'ActualPaid'
                ]);
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
            
        return view('admin.clients', compact('clients'));
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
        $lat = $request->lat;
        $lng = $request->lng;
        $date = $request->date; // YYYY-MM-DDTHH:ii format

        if (!$lat || !$lng) {
            return response()->json(['error' => 'Coordinates missing'], 400);
        }

        $weather = $this->weatherService->getWeather($lat, $lng, $date);
        
        if ($weather) {
            return response()->json($weather);
        }

        return response()->json(['error' => 'Could not fetch weather'], 500);
    }
}
