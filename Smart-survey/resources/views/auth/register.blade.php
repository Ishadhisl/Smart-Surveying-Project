@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4">
    <div class="glass p-8 sm:p-10 rounded-[2.5rem] shadow-2xl relative overflow-hidden bg-white/70 border border-white/60">
        <!-- Background Glow -->
        <div class="absolute -top-16 -right-16 w-56 h-56 bg-deepteal/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-56 h-56 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="text-center mb-8 relative z-10">
            <span class="inline-block px-3.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-deepteal/10 text-deepteal border border-deepteal/20 mb-3">
                Smart Survey Account Creation
            </span>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight">
                Join Smart Survey
            </h2>
            <p class="text-slate-500 text-xs sm:text-sm font-medium mt-1">Select your account type to proceed with registration</p>
        </div>

        {{-- Validation Errors --}}
        @if($errors->any())
        <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 mb-6 relative z-10">
            <ul class="list-disc list-inside text-red-600 text-xs font-bold space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST" id="register-form" class="space-y-6 relative z-10">
            @csrf
            
            {{-- Role State Input --}}
            <input type="hidden" name="role" id="selected_role" value="{{ old('role', $role ?? 'client') }}">

            {{-- Role Selection Cards (Upwork / Fiverr style) --}}
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                    Choose Your Role
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Client Card --}}
                    <div id="card-client" onclick="selectRole('client')"
                        class="role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between {{ old('role', $role ?? 'client') === 'client' ? 'border-deepteal bg-deepteal/5 shadow-md shadow-deepteal/10' : 'border-slate-200 bg-white/60 hover:border-slate-300' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ old('role', $role ?? 'client') === 'client' ? 'bg-deepteal text-white' : 'bg-slate-100 text-slate-600' }}" id="icon-client">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ old('role', $role ?? 'client') === 'client' ? 'border-deepteal bg-deepteal' : 'border-slate-300' }}" id="radio-client">
                                <div class="w-2 h-2 rounded-full bg-white"></div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-slate-900 tracking-wide">I am a Client</h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-1 leading-snug">
                                Request land surveys, view real-time site visit tracking & receive certified deeds.
                            </p>
                        </div>
                    </div>

                    {{-- Surveyor Card --}}
                    <div id="card-surveyor" onclick="selectRole('surveyor')"
                        class="role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between {{ old('role', $role ?? 'client') === 'surveyor' ? 'border-deepteal bg-deepteal/5 shadow-md shadow-deepteal/10' : 'border-slate-200 bg-white/60 hover:border-slate-300' }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ old('role', $role ?? 'client') === 'surveyor' ? 'bg-deepteal text-white' : 'bg-slate-100 text-slate-600' }}" id="icon-surveyor">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                            </div>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center {{ old('role', $role ?? 'client') === 'surveyor' ? 'border-deepteal bg-deepteal' : 'border-slate-300' }}" id="radio-surveyor">
                                <div class="w-2 h-2 rounded-full bg-white"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-black text-slate-900 tracking-wide">Licensed Surveyor</h3>
                                <span class="text-[9px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md">Verified</span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium mt-1 leading-snug">
                                Access Cadastral GIS Workspace, AI parcel division & manage official client jobs.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Professional Verification Notice (Visible when Surveyor is selected) --}}
            <div id="surveyor-verification-notice" class="{{ old('role', $role ?? 'client') === 'surveyor' ? 'block' : 'hidden' }} bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-2xl p-4 transition-all">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-amber-500 text-white rounded-xl flex-shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-amber-900 uppercase tracking-wider">Professional License Verification</h4>
                        <p class="text-[11px] text-amber-800 font-medium mt-0.5 leading-relaxed">
                            To ensure regulatory compliance, surveyor accounts require administrative verification of your Official Surveyor License Number before access to cadastral tools is granted.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Form Fields --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Full Name --}}
                <div class="form-group">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Full Name</label>
                    <input type="text" name="name" class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition" placeholder="John Silva" required value="{{ old('name') }}">
                    @error('name') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Email Address --}}
                <div class="form-group">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" name="email" class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition" placeholder="name@example.com" required value="{{ old('email') }}">
                    @error('email') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- WhatsApp Number --}}
                <div class="form-group">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition" placeholder="+94 7X XXX XXXX" required value="{{ old('whatsapp_number') }}">
                    @error('whatsapp_number') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                {{-- Surveyor License Number (Conditional Field) --}}
                <div id="license-number-group" class="form-group {{ old('role', $role ?? 'client') === 'surveyor' ? 'block' : 'hidden' }}">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">
                        Surveyor License Number <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="license_number" id="license_number_input"
                        class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition placeholder:text-slate-400"
                        placeholder="e.g. SURV-2024-001"
                        value="{{ old('license_number') }}">
                    @error('license_number') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Passwords --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Password</label>
                    <input type="password" name="password" id="reg-password" class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition" placeholder="Minimum 8 characters" required>
                    @error('password') <p class="text-red-500 text-[10px] mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="reg-password-confirmation" class="w-full bg-white/90 border border-slate-200 rounded-2xl px-4 py-3.5 text-slate-900 font-bold text-sm focus:border-deepteal focus:ring-4 focus:ring-deepteal/10 outline-none transition" placeholder="Repeat password" required>
                </div>
            </div>

            {{-- Submit Button --}}
            <button type="submit" id="submit-btn" class="w-full btn-teal py-4 rounded-2xl text-white font-black text-sm shadow-xl tracking-wider uppercase transition-all duration-300 flex items-center justify-center gap-2">
                <span id="submit-btn-text">
                    {{ old('role', $role ?? 'client') === 'surveyor' ? 'Submit Surveyor Application' : 'Create Client Account' }}
                </span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center relative z-10">
            <span class="text-slate-500 text-xs font-medium">Already have an account?</span>
            <a href="{{ route('login') }}" class="ml-1.5 font-extrabold text-deepteal hover:text-slate-900 transition uppercase text-xs tracking-wider underline underline-offset-4 decoration-deepteal/30">
                Sign In
            </a>
        </div>
    </div>
</div>

<script>
    function selectRole(role) {
        document.getElementById('selected_role').value = role;

        const cardClient = document.getElementById('card-client');
        const cardSurveyor = document.getElementById('card-surveyor');
        const iconClient = document.getElementById('icon-client');
        const iconSurveyor = document.getElementById('icon-surveyor');
        const radioClient = document.getElementById('radio-client');
        const radioSurveyor = document.getElementById('radio-surveyor');
        const licenseGroup = document.getElementById('license-number-group');
        const licenseInput = document.getElementById('license_number_input');
        const verificationNotice = document.getElementById('surveyor-verification-notice');
        const submitBtnText = document.getElementById('submit-btn-text');

        if (role === 'surveyor') {
            // Surveyor Card Active
            cardSurveyor.className = 'role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between border-deepteal bg-deepteal/5 shadow-md shadow-deepteal/10';
            iconSurveyor.className = 'w-10 h-10 rounded-xl flex items-center justify-center bg-deepteal text-white';
            radioSurveyor.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-deepteal bg-deepteal';

            // Client Card Inactive
            cardClient.className = 'role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between border-slate-200 bg-white/60 hover:border-slate-300';
            iconClient.className = 'w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 text-slate-600';
            radioClient.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-slate-300';

            // Show license field and notice
            licenseGroup.classList.remove('hidden');
            licenseInput.required = true;
            verificationNotice.classList.remove('hidden');
            submitBtnText.textContent = 'Submit Surveyor Application';
        } else {
            // Client Card Active
            cardClient.className = 'role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between border-deepteal bg-deepteal/5 shadow-md shadow-deepteal/10';
            iconClient.className = 'w-10 h-10 rounded-xl flex items-center justify-center bg-deepteal text-white';
            radioClient.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-deepteal bg-deepteal';

            // Surveyor Card Inactive
            cardSurveyor.className = 'role-card cursor-pointer p-5 rounded-2xl border-2 transition-all duration-300 relative flex flex-col justify-between border-slate-200 bg-white/60 hover:border-slate-300';
            iconSurveyor.className = 'w-10 h-10 rounded-xl flex items-center justify-center bg-slate-100 text-slate-600';
            radioSurveyor.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center border-slate-300';

            // Hide license field and notice
            licenseGroup.classList.add('hidden');
            licenseInput.required = false;
            licenseInput.value = '';
            verificationNotice.classList.add('hidden');
            submitBtnText.textContent = 'Create Client Account';
        }
    }
</script>
@endsection
