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
        $loginRole = $request->input('login_role', 'client');

        if ($loginRole === 'surveyor') {
            // Surveyor: Login via License Number + Password
            $request->validate([
                'license_number' => 'required|string',
                'password'       => 'required|string',
            ]);

            $user = User::where('license_number', $request->license_number)
                        ->where('role', 'admin')
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors([
                    'license_number' => 'Invalid License Number or password. Please try again.',
                ])->withInput();
            }

            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended('/admin/dashboard');

        } else {
            // Client: Login via Email + Password
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::where('email', $request->email)
                        ->where('role', 'client')
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return back()->withErrors([
                    'email' => 'Email or password is incorrect. Please try again.',
                ])->withInput();
            }

            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended('/');
        }
    }

    public function showRegister(Request $request)
    {
        $role = $request->get('role', 'client');
        return view('auth.register', compact('role'));
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,admin',
            'whatsapp_number' => 'required|string|max:20',
        ];

        if ($request->role === 'admin') {
            $rules['license_number'] = 'required|string|unique:users';
        }

        $validated = $request->validate($rules);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'license_number' => $validated['license_number'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'],
            'language' => app()->getLocale(),
        ]);

        Auth::login($user);

        if ($user->role === 'admin') {
            return redirect('/admin/dashboard');
        }
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
