<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('email'));

        // Allow login using either Email or Surveyor License Number seamlessly
        $user = User::where('email', $loginInput)
                    ->orWhere('license_number', $loginInput)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => 'Invalid credentials. Please verify your email/license number and password.',
            ])->withInput($request->only('email'));
        }

        // Check professional verification for surveyors with automatic registry check
        if ($user->role === 'admin' && !$user->is_approved) {
            $verificationService = app(\App\Services\SurveyorVerificationService::class);
            $verification = $verificationService->verify($user->license_number ?? '', $user->name);

            if ($verification['valid']) {
                $user->update([
                    'is_approved'    => true,
                    'license_number' => $verification['license_number'],
                ]);
            } else {
                return back()->withErrors([
                    'email' => 'Your surveyor account could not be verified automatically: ' . $verification['message'],
                ])->withInput($request->only('email'));
            }
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        return redirect()->intended('/');
    }

    public function showRegister(Request $request)
    {
        $role = $request->query('role', 'client');
        if (!in_array($role, ['client', 'surveyor'])) {
            $role = 'client';
        }

        return view('auth.register', compact('role'));
    }

    public function register(Request $request)
    {
        $role = $request->input('role', 'client');
        $isSurveyor = in_array($role, ['surveyor', 'admin']);

        $rules = [
            'name'            => 'required|string|max:255',
            'email'           => 'required|string|email|max:255|unique:users,email',
            'password'        => 'required|string|min:8|confirmed',
            'whatsapp_number' => 'required|string|max:20',
        ];

        if ($isSurveyor) {
            $rules['license_number'] = 'required|string|max:50|unique:users,license_number';
        }

        $validated = $request->validate($rules);

        if ($isSurveyor) {
            // Automated Registry Verification
            $verificationService = app(\App\Services\SurveyorVerificationService::class);
            $verification = $verificationService->verify($validated['license_number'], $validated['name']);

            if (!$verification['valid']) {
                return back()->withErrors([
                    'license_number' => $verification['message'],
                ])->withInput($request->except('password', 'password_confirmation'));
            }

            // Successfully Verified against Land Survey Council - Automatic Approval & Instant Access!
            $user = User::create([
                'name'            => $validated['name'],
                'email'           => $validated['email'],
                'password'        => Hash::make($validated['password']),
                'role'            => 'admin', // Internal surveyor role
                'license_number'  => $verification['license_number'],
                'whatsapp_number' => $validated['whatsapp_number'],
                'language'        => app()->getLocale(),
                'is_approved'     => true, // Automatically approved via official registry verification!
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/admin/dashboard')->with('success', 'Official Surveyor License (' . $verification['license_number'] . ') verified successfully! Welcome to your Surveyor Workspace.');
        }

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'role'            => 'client',
            'license_number'  => null,
            'whatsapp_number' => $validated['whatsapp_number'],
            'language'        => app()->getLocale(),
            'is_approved'     => true,
        ]);

        Auth::login($user);

        return redirect('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    // --- Password Reset Flow ---

    public function showForgotPassword()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Send reset link using Laravel's Default Password Broker
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword($token)
    {
        return view('auth.passwords.reset', ['token' => $token]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
