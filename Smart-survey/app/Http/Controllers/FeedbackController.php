<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display the feedback form for clients.
     */
    public function index()
    {
        $hasCompletedSurvey = \App\Models\Survey::where('user_id', Auth::id())
            ->where('status', 'completed')
            ->exists();

        if (!$hasCompletedSurvey) {
            return redirect()->route('survey.index')->with('warning', 'You can only provide feedback after at least one of your survey projects is completed.');
        }

        return view('client.feedback');
    }

    /**
     * Store a newly created feedback in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        Feedback::create([
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return redirect()->back()->with('success', 'Thank you for your feedback!');
    }

    /**
     * Display all feedbacks for admin.
     */
    public function adminIndex()
    {
        $feedbacks = Feedback::with('user')->latest()->paginate(10);
        return view('admin.feedbacks', compact('feedbacks'));
    }
}
