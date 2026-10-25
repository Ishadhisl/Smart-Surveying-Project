@extends('layouts.app')

@section('content')
    <div class="min-h-[75vh] flex items-center justify-center py-10 px-4">
        <div class="w-full max-w-md">

            {{-- Flash Messages (e.g. Verification Pending Notice or Status) --}}
            @if (session('status'))
                <div
                    class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-900 rounded-3xl p-5 text-sm font-semibold flex items-start gap-3 backdrop-blur-md shadow-lg">
                    <div class="p-2 bg-emerald-500 text-white rounded-xl flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <p class="leading-relaxed text-xs font-bold">{{ session('status') }}</p>
                </div>
            @endif

            {{-- Flash Errors --}}
            @if($errors->any())
                <div
                    class="mb-6 bg-red-500/10 border border-red-500/30 text-red-900 rounded-3xl p-5 text-sm font-semibold backdrop-blur-md shadow-lg">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-red-500 text-white rounded-xl flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                        </div>
                        <ul class="space-y-1 text-xs font-bold pt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div
                class="glass rounded-[2.5rem] shadow-[0_30px_80px_rgba(0,0,0,0.18)] overflow-hidden border border-white/40">

                {{-- Header --}}
                <div
                    class="bg-gradient-to-br from-slate-900 via-slate-800 to-deepteal px-8 pt-10 pb-8 text-center relative overflow-hidden">
                    <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
                    <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-deepteal/30 rounded-full blur-2xl"></div>

                    <div class="relative z-10">
                        <div
                            class="inline-flex items-center justify-center w-14 h-14 bg-white/10 rounded-2xl border border-white/20 mb-3 backdrop-blur-sm shadow-inner">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-extrabold text-white tracking-tight mb-1">Smart Survey</h1>
                        <p class="text-white/60 text-[11px] uppercase tracking-[0.2em] font-bold">Secure Access Portal</p>
                    </div>
                </div>

                {{-- Universal Login Form (Single secure endpoint for all accounts) --}}
                <form action="{{ route('login') }}" method="POST" class="px-8 py-8 space-y-5">
                    @csrf

                    {{-- Identifier (Email) --}}
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-deepteal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="email" id="email" required autofocus
                                class="w-full pl-11 pr-4 py-3.5 bg-white/80 border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 transition-all placeholder:text-slate-400"
                                placeholder="name@example.com" value="{{ old('email') }}">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                Password
                            </label>
                            <a href="{{ route('password.request') }}"
                                class="text-[10px] font-black text-deepteal hover:text-slate-900 transition uppercase tracking-wider underline underline-offset-4 decoration-deepteal/30">
                                Forgot?
                            </a>
                        </div>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full pl-11 pr-12 py-3.5 bg-white/80 border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 transition-all placeholder:text-slate-400"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePasswordVisibility()"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition-colors"
                                title="Toggle password visibility">
                                <svg id="password-toggle-icon" class="w-5 h-5" fill="none" stroke="currentColor"
                                    stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember"
                                class="w-4 h-4 rounded border-slate-300 text-deepteal focus:ring-deepteal/30">
                            <span class="text-xs font-bold text-slate-600">Keep me signed in</span>
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit"
                        class="w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-xl transition-all duration-300 bg-deepteal hover:bg-[#006666] hover:-translate-y-0.5 hover:shadow-2xl hover:shadow-deepteal/30 flex items-center justify-center gap-2">
                        <span>Sign In</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>

                    {{-- Footer Register Link --}}
                    <div class="pt-6 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-500 font-medium">
                            Don't have an account?
                            <a href="{{ route('register') }}"
                                class="font-extrabold text-deepteal hover:text-slate-900 transition ml-1 uppercase text-xs tracking-wider underline underline-offset-4 decoration-deepteal/30">
                                Create Account
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
            }
        }
    </script>
@endsection