@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center">
<div class="w-full max-w-lg">

    {{-- Flash Messages --}}
    @if (session('status'))
    <div class="mb-6 bg-emerald-100/80 border border-emerald-300 text-emerald-800 rounded-2xl p-4 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        {{ session('status') }}
    </div>
    @endif

    {{-- Flash Errors --}}
    @if($errors->any())
    <div class="mb-6 bg-red-100/80 border border-red-300 text-red-800 rounded-2xl p-4 text-sm font-semibold">
        <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="glass rounded-[2.5rem] shadow-[0_30px_80px_rgba(0,0,0,0.18)] overflow-hidden border border-white/30">

        {{-- Header --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-deepteal px-10 pt-10 pb-8 text-center relative overflow-hidden">
            <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-deepteal/30 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-white/10 rounded-2xl border border-white/20 mb-4 backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-white tracking-tight mb-1">Smart Survey</h1>
                <p class="text-white/50 text-[11px] uppercase tracking-[0.2em] font-bold">Professional Access Portal</p>
            </div>
        </div>

        {{-- Role Selector Toggle --}}
        <div class="px-8 pt-8 pb-2">
            <p class="text-center text-[10px] font-black text-slate-500 uppercase tracking-widest mb-4">Select Your Role to Continue</p>
            <div class="grid grid-cols-2 gap-2 bg-slate-100/80 p-1.5 rounded-2xl">
                <button type="button" id="btn-surveyor" onclick="switchRole('surveyor')"
                    class="role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 bg-deepteal text-white shadow-lg shadow-deepteal/30">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    Surveyor
                </button>
                <button type="button" id="btn-client" onclick="switchRole('client')"
                    class="role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 text-slate-500 hover:text-slate-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Client
                </button>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('login') }}" method="POST" class="px-8 pb-8 pt-4 space-y-5">
            @csrf
            <input type="hidden" name="login_role" id="login_role_input" value="surveyor">

            {{-- Role Badge --}}
            <div class="text-center">
                <span id="role-badge" class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-deepteal/10 text-deepteal border border-deepteal/20">
                    Licensed Surveyor Login
                </span>
            </div>

            {{-- SURVEYOR FIELDS --}}
            <div id="surveyor-fields">
                <div class="space-y-4">
                    {{-- License Number --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                            Surveyor License Number
                        </label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-deepteal">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <input type="text" name="license_number" id="license_number"
                                class="w-full pl-10 pr-5 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all"
                                placeholder="e.g. LS-2024-001"
                                value="{{ old('license_number') }}">
                        </div>
                        @error('license_number')
                            <p class="text-red-500 text-[10px] mt-1.5 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input type="password" name="password" id="password_surveyor"
                                class="w-full pl-10 pr-12 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePasswordField('password_surveyor', 'eye-surveyor')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition-colors">
                                <svg id="eye-surveyor" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex justify-end mt-2 px-1">
                            <a href="{{ route('password.request') }}" class="text-[10px] font-black text-slate-900/60 uppercase tracking-widest hover:text-deepteal hover:underline underline-offset-4 transition-all">
                                Forgot Password?
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CLIENT FIELDS --}}
            <div id="client-fields" class="hidden">
                <div class="space-y-4">
                    {{-- Client Name --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Your Full Name</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input type="text" name="client_name" id="client_name"
                                class="w-full pl-10 pr-5 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 transition-all"
                                placeholder="John Silva"
                                value="{{ old('client_name') }}">
                        </div>
                    </div>

                    {{-- Client Email --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input type="email" name="email"
                                class="w-full pl-10 pr-5 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 transition-all"
                                placeholder="client@example.com"
                                value="{{ old('email') }}">
                        </div>
                        @error('email')
                            <p class="text-red-500 text-[10px] mt-1.5 font-bold">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Client Password --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input type="password" name="password" id="password_client"
                                class="w-full pl-10 pr-12 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10 transition-all"
                                placeholder="Enter your password">
                            <button type="button" onclick="togglePasswordField('password_client', 'eye-client')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-amber-500 transition-colors">
                                <svg id="eye-client" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex justify-end mt-2 px-1">
                            <a href="{{ route('password.request') }}" class="text-[10px] font-black text-slate-900/60 uppercase tracking-widest hover:text-amber-600 hover:underline underline-offset-4 transition-all">
                                Forgot Password?
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit" id="login-btn"
                class="w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-lg transition-all duration-300 bg-deepteal hover:bg-[#006666] hover:-translate-y-0.5 hover:shadow-xl hover:shadow-deepteal/30">
                <span id="btn-text">Login as Surveyor</span>
                <svg class="w-4 h-4 inline-block ml-2 -mt-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>

            {{-- Footer Links --}}
            <div class="pt-4 border-t border-slate-100 text-center space-y-3">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Don't have an account?</p>
                <div class="flex justify-center gap-6">
                    <a href="{{ route('register', ['role' => 'admin']) }}"
                        class="text-[11px] font-black text-deepteal hover:text-slate-900 transition uppercase tracking-wider underline underline-offset-4 decoration-deepteal/30">
                        Register as Surveyor
                    </a>
                    <a href="{{ route('register', ['role' => 'client']) }}"
                        class="text-[11px] font-black text-amber-600 hover:text-slate-900 transition uppercase tracking-wider underline underline-offset-4 decoration-amber-400/30">
                        Register as Client
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

@push('scripts')
<script>
    const roles = {
        surveyor: {
            badge: 'Licensed Surveyor Login',
            badgeClass: 'bg-deepteal/10 text-deepteal border-deepteal/20',
            btnText: 'Login as Surveyor',
            btnClass: 'bg-deepteal hover:bg-[#006666] hover:shadow-deepteal/30',
        },
        client: {
            badge: 'Client Portal Login',
            badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
            btnText: 'Login as Client',
            btnClass: 'bg-amber-600 hover:bg-amber-700 hover:shadow-amber-500/30',
        }
    };

    function switchRole(role) {
        const r = roles[role];
        document.getElementById('login_role_input').value = role;

        // Update badge
        const badge = document.getElementById('role-badge');
        badge.textContent = r.badge;
        badge.className = `inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all duration-300 ${r.badgeClass}`;

        // Update button
        const btn = document.getElementById('login-btn');
        btn.className = `w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl ${r.btnClass}`;
        document.getElementById('btn-text').textContent = r.btnText;

        // Toggle fields
        document.getElementById('surveyor-fields').classList.toggle('hidden', role !== 'surveyor');
        document.getElementById('client-fields').classList.toggle('hidden', role !== 'client');

        // Toggle required attributes
        document.getElementById('license_number').required = (role === 'surveyor');
        document.getElementById('password_surveyor').disabled = (role !== 'surveyor');
        document.getElementById('password_client').disabled = (role === 'surveyor');
        document.getElementById('password_client').required = (role === 'client');
        document.getElementById('client_name').required = (role === 'client');

        // Update active button style
        const btnSurveyor = document.getElementById('btn-surveyor');
        const btnClient = document.getElementById('btn-client');

        if (role === 'surveyor') {
            btnSurveyor.className = 'role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 bg-deepteal text-white shadow-lg shadow-deepteal/30';
            btnClient.className = 'role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 text-slate-500 hover:text-slate-700';
        } else {
            btnClient.className = 'role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 bg-amber-500 text-white shadow-lg shadow-amber-500/30';
            btnSurveyor.className = 'role-btn flex items-center justify-center gap-2.5 px-4 py-3.5 rounded-xl text-sm font-black uppercase tracking-wider transition-all duration-300 text-slate-500 hover:text-slate-700';
        }
    }

    function togglePasswordField(id, iconId) {
        const input = document.getElementById(id);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        }
    }

    // Initialize - surveyor is default, disable client password
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('password_client').disabled = true;
        document.getElementById('password_surveyor').name = 'password';
    });
</script>
@endpush
@endsection
