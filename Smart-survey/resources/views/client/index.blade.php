@extends('layouts.app')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-center mb-12 gap-4">
    <h2 class="text-4xl font-extrabold text-slate-900 tracking-tighter">{{ __('messages.tracking') }}</h2>
    <a href="{{ route('survey.create') }}" class="btn-teal px-8 py-4 rounded-2xl text-white font-black flex items-center gap-3 shadow-xl uppercase tracking-widest text-xs">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"></path></svg>
        {{ __('messages.new_survey') }}
    </a>
</div>

{{-- Balance Reminder Banner (New) --}}
@php
    $pendingPaymentSurvey = $surveys->whereIn('payment_status', ['pending', 'advance_paid'])->where('status', '!=', 'requested')->first();
@endphp

@if($pendingPaymentSurvey)
<div class="glass mb-10 p-6 rounded-[2rem] border-amber-500/20 bg-amber-500/5 shadow-xl animate-pulse">
    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex items-center gap-4 text-center md:text-left">
            <div class="w-12 h-12 bg-amber-500/20 rounded-2xl flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Action Required</p>
                <h4 class="text-lg font-black text-slate-800">Pending Payment for {{ $pendingPaymentSurvey->village }}</h4>
                <p class="text-xs text-slate-600 font-bold">Please settle the outstanding balance to avoid delays in your survey project.</p>
            </div>
        </div>
        <a href="{{ route('survey.show', $pendingPaymentSurvey) }}" class="bg-amber-500 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-amber-500/30 hover:scale-105 transition-transform">
            Pay Now Rs. {{ number_format($pendingPaymentSurvey->balance_amount ?: ($pendingPaymentSurvey->estimate_price / 3)) }}
        </a>
    </div>
</div>
@endif

@if($surveys->isEmpty())
    <div class="glass rounded-[3rem] p-24 text-center border-white/40 shadow-2xl">
        <div class="text-7xl mb-8 grayscale opacity-20">📂</div>
        <p class="text-slate-900 text-xl mb-8 font-black uppercase tracking-widest opacity-40">No Projects Found</p>
        <a href="{{ route('survey.create') }}" class="btn-teal px-10 py-5 rounded-2xl inline-block font-black shadow-lg">Start Formal Request &rarr;</a>
    </div>
@else
    <div class="grid gap-8">
        @foreach($surveys as $survey)
            <a href="{{ route('survey.show', $survey) }}" class="group block">
                <div class="glass p-8 rounded-[2.5rem] flex flex-col md:flex-row justify-between items-center transition-all bg-white/20 hover:bg-white/40 group-hover:-translate-y-1 border-white/50 shadow-xl group-hover:border-white">
                    <div class="mb-6 md:mb-0 text-center md:text-left">
                        <div class="text-[10px] font-black text-slate-900/40 uppercase tracking-[0.2em] mb-2">Project Location</div>
                        <h3 class="text-2xl font-black text-slate-900 mb-3">{{ $survey->village }}</h3>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-xs text-slate-900 font-bold opacity-70">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ ucfirst(str_replace('_', ' ', $survey->purpose)) }}
                            </span>
                            <span class="opacity-30">|</span>
                            <span>{{ $survey->size_perches }} {{ __('messages.perches') }}</span>
                            <span class="opacity-30">|</span>
                            <span>{{ $survey->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-6">
                        @if($survey->status == 'completed')
                            <a href="{{ route('feedback.index') }}" class="btn-teal px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest shadow-md hover:scale-105 transition-transform flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                Give Feedback
                            </a>
                        @endif
                        <span class="px-6 py-2 rounded-full text-[10px] font-black uppercase tracking-[0.15em] shadow-sm border
                            @if($survey->status == 'completed') bg-emerald-500/10 text-emerald-700 border-emerald-500/20
                            @elseif($survey->status == 'requested') bg-blue-500/10 text-blue-700 border-blue-500/20
                            @else bg-amber-500/10 text-amber-700 border-amber-500/20 @endif">
                            {{ __('messages.' . $survey->status) }}
                        </span>
                        <div class="w-12 h-12 rounded-2xl bg-white shadow-lg flex items-center justify-center text-deepteal group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"></path></svg>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection
