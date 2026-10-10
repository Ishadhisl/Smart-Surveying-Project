<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

// Home Page
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Client Routes (Protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/survey/new', [SurveyController::class, 'create'])->name('survey.create');
    Route::post('/survey/new', [SurveyController::class, 'store'])->name('survey.store');
    Route::get('/survey/tracking', [SurveyController::class, 'index'])->name('survey.index');
    Route::get('/survey/{survey}', [SurveyController::class, 'show'])->name('survey.show');
    Route::post('/survey/{survey}/respond-schedule', [SurveyController::class, 'respondToSchedule'])->name('survey.respond');

    // Payment Routes
    Route::get('/survey/{survey}/pay', [PaymentController::class, 'showPaymentPage'])->name('payment.page');
    Route::post('/survey/{survey}/upload-slip', [PaymentController::class, 'uploadSlip'])->name('payment.slip');
    Route::get('/payment/return', [PaymentController::class, 'payHereReturn'])->name('payment.return');
    Route::get('/survey/{survey}/invoice', [SurveyController::class, 'downloadInvoice'])->name('survey.invoice');

    // Feedback Routes
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // AR View Route
    Route::get('/survey/{survey}/ar-view', [\App\Http\Controllers\BoundaryPointController::class, 'arView'])->name('survey.ar-view');

    // Session Management Routes
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::delete('/sessions', [SessionController::class, 'destroyOthers'])->name('sessions.destroyOthers');
});

// PayHere Server Notification (no CSRF)
Route::post('/payment/notify', [PaymentController::class, 'payHereNotify'])
    ->name('payment.notify')
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Admin/Surveyor Routes (Protected + Role Check)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
        Route::get('/dashboard', [SurveyController::class, 'adminDashboard'])->name('dashboard');
        Route::get('/projects', [SurveyController::class, 'adminProjects'])->name('projects');
        Route::get('/upcoming-visits', [SurveyController::class, 'adminUpcomingVisits'])->name('upcoming-visits');
        Route::get('/clients', [SurveyController::class, 'adminClients'])->name('clients');
        Route::get('/deeds', [SurveyController::class, 'adminDeeds'])->name('deeds');
        Route::post('/survey/{survey}/update-status', [SurveyController::class, 'updateStatus'])->name('status.update');
        Route::post('/survey/{survey}/propose-schedule', [SurveyController::class, 'proposeSchedule'])->name('schedule.propose');
        Route::post('/survey/{survey}/mark-payment', [SurveyController::class, 'markPayment'])->name('payment.mark');
        Route::get('/weather-check', [SurveyController::class, 'checkWeather'])->name('weather.check');
        Route::post('/survey/{survey}/boundary-points', [\App\Http\Controllers\BoundaryPointController::class, 'store'])->name('boundary.store');

        // Admin Feedback Route
        Route::get('/feedbacks', [FeedbackController::class, 'adminIndex'])->name('feedbacks');
    });
});
