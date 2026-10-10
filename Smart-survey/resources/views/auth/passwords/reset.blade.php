@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
<div class="w-full max-w-md">

    {{-- Errors --}}
    @if($errors->any())
    <div class="mb-6 bg-red-100/80 border border-red-300 text-red-800 rounded-2xl p-4 text-sm font-semibold">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="glass rounded-[2.5rem] shadow-[0_30px_80px_rgba(0,0,0,0.18)] overflow-hidden border border-white/30">
        {{-- Header --}}
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-deepteal px-8 pt-10 pb-8 text-center relative overflow-hidden">
            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-white/10 rounded-2xl border border-white/20 mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight mb-1">Set New Password</h1>
                <p class="text-white/50 text-[10px] uppercase tracking-[0.2em] font-bold">Secure Account Access</p>
            </div>
        </div>

        <form action="{{ route('password.update') }}" method="POST" class="p-8 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-5 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all shadow-inner"
                        placeholder="your@email.com" value="{{ old('email') }}">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">New Password</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" required
                        class="w-full pl-10 pr-12 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all shadow-inner"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePasswordField('password', 'eye-new')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition-colors">
                        <svg id="eye-new" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Confirm New Password</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full pl-10 pr-12 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all shadow-inner"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePasswordField('password_confirmation', 'eye-conf')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition-colors">
                        <svg id="eye-conf" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" 
                class="w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-lg transition-all duration-300 bg-slate-900 hover:bg-slate-800 hover:-translate-y-0.5 hover:shadow-xl">
                Reset Password
            </button>
        </form>
    </div>
</div>
</div>
@push('scripts')
<script>
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
</script>
@endpush
@endsection
