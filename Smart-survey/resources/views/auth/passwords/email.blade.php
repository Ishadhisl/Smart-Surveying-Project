@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
<div class="w-full max-w-md">

    {{-- Status Alert --}}
    @if (session('status'))
    <div class="mb-6 bg-emerald-100/80 border border-emerald-300 text-emerald-800 rounded-2xl p-4 text-sm font-semibold flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        {{ session('status') }}
    </div>
    @endif

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
                        <path d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-extrabold text-white tracking-tight mb-1">Reset Password</h1>
                <p class="text-white/50 text-[10px] uppercase tracking-[0.2em] font-bold">Secure Account Recovery</p>
            </div>
        </div>

        <form action="{{ route('password.email') }}" method="POST" class="p-8 space-y-6">
            @csrf
            
            <p class="text-[11px] text-slate-600 font-bold leading-relaxed text-center">
                Enter your registered email address and we'll send you a secure link to reset your password.
            </p>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                <div class="relative">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <input type="email" name="email" required
                        class="w-full pl-10 pr-5 py-3.5 bg-white border border-slate-200 rounded-2xl text-slate-900 font-bold text-sm focus:outline-none focus:border-deepteal focus:ring-2 focus:ring-deepteal/10 transition-all"
                        placeholder="your@email.com" value="{{ old('email') }}">
                </div>
            </div>

            <button type="submit" 
                class="w-full py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-lg transition-all duration-300 bg-deepteal hover:bg-[#006666] hover:-translate-y-0.5 hover:shadow-xl">
                Send Reset Link
            </button>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-deepteal transition">
                    ← Back to Login
                </a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
