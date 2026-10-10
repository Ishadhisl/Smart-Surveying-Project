@extends('layouts.admin')

@section('admin-content')

<div class="mb-8">
    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tighter">Upcoming Visits</h1>
    <p class="text-white font-bold opacity-60 tracking-wide uppercase text-[10px] mt-1">Scheduled Site Visits — Next 7 Days</p>
</div>

<div class="glass rounded-[2rem] shadow-xl overflow-hidden">
    @if($upcomingVisits->count() > 0)
    <div class="divide-y divide-white/20">
        @foreach($upcomingVisits as $visit)
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 px-8 py-6 hover:bg-white/15 transition-all">
            <div class="flex items-center gap-5">
                {{-- Date Badge --}}
                <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center font-extrabold shadow-md flex-shrink-0 {{ $visit->scheduled_at->isToday() ? 'bg-red-500 text-white' : 'bg-deepteal text-white' }}">
                    <span class="text-xl leading-tight">{{ $visit->scheduled_at->format('d') }}</span>
                    <span class="text-[9px] uppercase font-black opacity-80">{{ $visit->scheduled_at->format('M') }}</span>
                </div>

                <div>
                    <p class="font-extrabold text-slate-900 text-base">{{ $visit->user->name }}</p>
                    <p class="text-sm text-slate-600 font-semibold">{{ $visit->village }}</p>
                    @php
                        $pNumber = preg_replace('/[^0-9]/', '', $visit->user->whatsapp_number);
                        $formattedPhone = str_starts_with($pNumber, '0') ? '94' . substr($pNumber, 1) : (str_starts_with($pNumber, '94') ? $pNumber : '94' . $pNumber);
                    @endphp
                    <a href="https://wa.me/{{ $formattedPhone }}"
                        target="_blank"
                        class="text-xs text-deepteal font-bold hover:underline">
                        {{ $visit->user->whatsapp_number }}
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-6">
                {{-- Weather Details --}}
                @if(isset($visit->weather))
                <div class="flex items-center gap-3 bg-white/40 px-4 py-2 rounded-2xl border border-white/50">
                    <span class="text-2xl">{{ $visit->weather['icon'] }}</span>
                    <div>
                        <p class="text-xs font-black text-slate-900 leading-tight">{{ $visit->weather['temp'] }}°C</p>
                        <p class="text-[9px] text-slate-500 font-bold uppercase tracking-tighter">{{ $visit->weather['description'] }}</p>
                    </div>
                </div>
                @endif

                <div class="text-right">
                    <p class="font-extrabold text-slate-900 text-lg">{{ $visit->scheduled_at->format('h:i A') }}</p>
                    <p class="text-xs text-slate-500 font-semibold">{{ $visit->scheduled_at->format('l, M d Y') }}</p>
                    @if($visit->scheduled_at->isToday())
                    <span class="inline-block bg-red-100/90 text-red-600 text-[10px] font-black px-3 py-0.5 rounded-full mt-1">TODAY</span>
                    @else
                    <span class="inline-block bg-deepteal/10 text-deepteal text-[10px] font-black px-3 py-0.5 rounded-full mt-1">
                        In {{ now()->diffInDays($visit->scheduled_at) }} Day(s)
                    </span>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    @if($visit->location_lat)
                    <button type="button" onclick="getLiveDirections(this, {{ $visit->location_lat }}, {{ $visit->location_lng }})" class="action-btn abtn-blue flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Get Directions</span>
                    </button>
                    @endif
                    @php
                        $pNumRem = preg_replace('/[^0-9]/', '', $visit->user->whatsapp_number);
                        $waPhoneRem = str_starts_with($pNumRem, '0') ? '94' . substr($pNumRem, 1) : (str_starts_with($pNumRem, '94') ? $pNumRem : '94' . $pNumRem);
                        $msg = "Hi {$visit->user->name}, this is a reminder for your site visit at {$visit->village} on " . $visit->scheduled_at->format('M d, Y h:i A') . ". Please ensure the site is accessible.";
                    @endphp
                    <a href="https://wa.me/{{ $waPhoneRem }}?text={{ urlencode($msg) }}"
                        target="_blank" class="action-btn abtn-teal">
                        📱 Send Reminder
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-24">
        <svg class="w-14 h-14 text-white/20 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="text-white/40 font-black uppercase tracking-widest text-sm">No upcoming visits in the next 7 days.</p>
        <p class="text-white/25 text-xs mt-2">Schedule visits from Job Management.</p>
        <a href="{{ url('/admin/projects') }}" class="inline-block mt-5 btn-teal px-6 py-2.5 rounded-2xl font-black text-xs uppercase tracking-widest shadow-md">
            Go to Job Management
        </a>
    </div>
    @endif
</div>

@endsection
