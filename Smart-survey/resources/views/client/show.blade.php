@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="mb-10 flex items-center gap-4">
        <a href="{{ route('survey.index') }}" class="btn-glass p-3 rounded-xl text-white hover:bg-white/20 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <h2 class="text-3xl font-extrabold text-white tracking-tight">Project Status: {{ $survey->village }}</h2>
    </div>

    <!-- Modern Stepper UI -->
    <div class="glass p-10 rounded-[2.5rem] mb-12 overflow-x-auto">
        <div class="min-w-[800px] relative flex justify-between items-center px-4">
            <!-- Background Progress Line -->
            <div class="absolute top-[40px] left-8 right-8 h-1 bg-white/10 z-0"></div>
            
            @php
                $statuses = ['requested', 'confirmed', 'advance_paid', 'site_visit', 'actual_paid', 'mapping', 'completed'];
                $currentIdx = array_search($survey->status, $statuses);

                // Adjust index based on payment status if it's ahead of current status
                if ($survey->payment_status === 'fully_paid' && $currentIdx < 4) {
                    $currentIdx = 4; // actual_paid step
                } elseif ($survey->payment_status === 'advance_paid' && $currentIdx < 2) {
                    $currentIdx = 2; // advance_paid step
                }
            @endphp

            @foreach($statuses as $index => $status)
                <div class="z-10 flex flex-col items-center group">
                    <!-- Progress Circle -->
                    <div class="w-20 h-20 rounded-full flex items-center justify-center transition-all duration-500 mb-4
                        {{ $index <= $currentIdx ? 'bg-deepteal shadow-[0_0_20px_rgba(0,128,128,0.5)] border-4 border-white/20' : 'bg-gray-800 border-2 border-white/10' }}">
                        
                        @if($index < $currentIdx)
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        @elseif($index == $currentIdx)
                            <div class="animate-pulse w-4 h-4 rounded-full bg-white"></div>
                        @else
                            <span class="text-gray-500 font-bold text-xl">{{ $index + 1 }}</span>
                        @endif
                    </div>
                    
                    <!-- Label -->
                    <div class="text-[10px] font-black uppercase tracking-widest text-center px-2 py-1 rounded-lg
                        {{ $index <= $currentIdx ? 'text-white bg-deepteal/20' : 'text-gray-600' }}">
                        {{ str_replace('_', ' ', $status) }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Details Section -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Handshake UI for Scheduling -->
            @if($survey->schedule_status === 'proposed')
            <div class="bg-gradient-to-r from-deepteal to-slate-900 p-1 rounded-[2.5rem] shadow-2xl animate-pulse">
                <div class="bg-slate-900 rounded-[2.4rem] p-10">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                        <div>
                            <span class="text-[10px] font-black text-deepteal uppercase tracking-[0.3em] block mb-2">Responce Required</span>
                            <h3 class="text-2xl font-black text-white mb-2">Surveyor Proposed a Date</h3>
                            <p class="text-white/60 text-sm font-bold">The surveyor is ready to visit your site on:</p>
                        </div>
                        <div class="bg-white/5 border border-white/10 p-6 rounded-3xl text-center min-w-[200px]">
                            <div class="text-3xl font-black text-white">{{ $survey->proposed_at->format('M d') }}</div>
                            <div class="text-xs font-black text-deepteal uppercase tracking-widest mt-1">{{ $survey->proposed_at->format('h:i A') }}</div>
                            @if(!empty($weather) && ($weather['status'] ?? '') === 'available')
                                <div class="mt-3 pt-3 border-t border-white/10 flex items-center justify-center gap-2.5 text-left">
                                    <span class="text-2xl drop-shadow">{{ $weather['icon'] ?? '⛅' }}</span>
                                    <div>
                                        <div class="text-xs font-black text-white leading-tight">{{ $weather['temp'] }}°C · {{ $weather['description'] }}</div>
                                        <div class="text-[9px] font-bold {{ ($weather['risk'] ?? '') === 'High' ? 'text-rose-400' : (($weather['risk'] ?? '') === 'Medium' ? 'text-amber-400' : 'text-emerald-400') }}">
                                            Field Risk: {{ $weather['risk'] }} · 💧 {{ $weather['precipitation_sum'] }}mm
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex gap-4">
                            <form action="{{ route('survey.respond', $survey) }}" method="POST">
                                @csrf
                                <input type="hidden" name="response" value="accept">
                                <button type="submit" class="btn-teal px-8 py-4 rounded-2xl text-white font-black uppercase text-xs tracking-widest shadow-xl hover:scale-105 transition-transform">Accept Date</button>
                            </form>
                            <form action="{{ route('survey.respond', $survey) }}" method="POST">
                                @csrf
                                <input type="hidden" name="response" value="decline">
                                <button type="submit" class="bg-red-900/20 border border-red-900/40 px-8 py-4 rounded-2xl text-red-500 font-black uppercase text-xs tracking-widest hover:bg-red-900/40 transition-all">Decline</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="glass p-8 rounded-3xl">
                <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                    <svg class="w-5 h-5 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"></path></svg>
                    Project Information
                </h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Village</label>
                        <span class="text-white font-semibold">{{ $survey->village }}</span>
                    </div>
                    <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Purpose</label>
                        <span class="text-white font-semibold">{{ ucfirst(str_replace('_', ' ', $survey->purpose)) }}</span>
                    </div>
                    <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Size</label>
                        <span class="text-white font-semibold">
                            @if($survey->size_acres > 0)
                                {{ $survey->size_acres + 0 }} Acres, 
                            @endif
                            {{ $survey->size_perches + 0 }} Perches
                        </span>
                    </div>
                    <div class="bg-white/5 p-4 rounded-2xl border border-white/5">
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Points</label>
                        <span class="text-white font-semibold">{{ $survey->boundary_points }} Points</span>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden border border-white/10 grayscale hover:grayscale-0 transition-all duration-700 mb-6">
                    <div id="map" class="h-80 w-full"></div>
                </div>

                @if($survey->boundaryPoints && $survey->boundaryPoints->count() > 0)
                <div class="bg-gradient-to-r from-deepteal/20 to-emerald-500/20 border border-deepteal/30 p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <h4 class="text-white font-black text-lg mb-1 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Live AR Boundary Viewer
                        </h4>
                        <p class="text-xs text-white/70 font-semibold max-w-sm">Experience your land boundaries in real-world 3D. Go to your land, open this link on your mobile phone, and look around using the camera.</p>
                    </div>
                    <a href="{{ route('survey.ar-view', $survey) }}" class="btn-teal px-8 py-4 rounded-xl text-white font-black uppercase text-xs tracking-widest shadow-xl shadow-deepteal/30 hover:scale-105 transition-transform flex-shrink-0 whitespace-nowrap">
                        Launch AR View &rarr;
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Sidebar Actions/Stats -->
        <div class="lg:col-span-1 space-y-8">
            <!-- Payment Card -->
            <div class="glass p-8 rounded-3xl border-deepteal/20 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg class="w-20 h-20 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14H11V15H12V13H11V11H13V16zM13 9H11V7H13V9z"></path></svg>
                </div>

                <h3 class="text-lg font-bold text-white mb-6">Financial Progress</h3>

                <div class="space-y-6">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase tracking-widest block mb-1">Estimated Base Fee</label>
                        <div class="text-3xl font-black text-white">Rs. {{ number_format($survey->estimate_price) }}</div>
                    </div>

                    @if($survey->payment_status === 'pending')
                        <div class="bg-yellow-500/10 border border-yellow-500/30 p-5 rounded-2xl">
                            <p class="text-[10px] font-black text-yellow-500 uppercase mb-2 flex items-center gap-2">
                                Upcoming: Advance Payment (1/3)
                            </p>
                            <div class="text-2xl font-black text-white">Rs. {{ number_format($survey->estimate_price / 3) }}</div>
                            
                            @if($survey->payment_slip_path && $survey->payment_slip_type === 'advance')
                                <div class="bg-yellow-500/20 border border-yellow-500/40 p-3 rounded-xl mt-4 text-center">
                                    <span class="text-[10px] font-black text-yellow-500 uppercase tracking-widest block mb-1">Slip Uploaded</span>
                                    <span class="text-xs font-bold text-white/80">Pending surveyor verification...</span>
                                </div>
                            @else
                                <a href="{{ route('payment.page', $survey) }}" class="btn-teal w-full py-3 rounded-xl mt-4 text-center font-black uppercase text-[10px] tracking-widest shadow-lg block">
                                    Pay Advance & Continue
                                </a>
                            @endif
                            <p class="text-[9px] text-yellow-200/40 mt-3 font-bold uppercase tracking-tighter text-center">Status updates once verified by Surveyor</p>
                        </div>
                    @elseif($survey->payment_status === 'advance_paid')
                        <div class="bg-deepteal/10 border border-deepteal/30 p-5 rounded-2xl">
                            <p class="text-[10px] font-black text-deepteal uppercase mb-2">Advance Settled</p>
                            <div class="text-xl font-black text-white line-through opacity-30">Rs. {{ number_format($survey->estimate_price / 3) }}</div>
                            
                            @if($survey->actual_price || in_array($survey->status, ['site_visit', 'mapping', 'completed']))
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">
                                    {{ $survey->actual_price ? 'Actual Final Price' : 'Estimated Total Fee' }}
                                </label>
                                <div class="text-2xl font-black text-white mb-4">Rs. {{ number_format($survey->actual_price ?? $survey->estimate_price) }}</div>
                                
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Remaining Balance To Pay</label>
                                <div class="text-3xl font-black text-deepteal">Rs. {{ number_format($survey->balance_amount) }}</div>

                                @if($survey->payment_slip_path && $survey->payment_slip_type === 'balance')
                                    <div class="bg-deepteal/20 border border-deepteal/40 p-3 rounded-xl mt-5 text-center">
                                        <span class="text-[10px] font-black text-deepteal uppercase tracking-widest block mb-1">Slip Uploaded</span>
                                        <span class="text-xs font-bold text-white/80">Pending surveyor verification...</span>
                                    </div>
                                @else
                                    <a href="{{ route('payment.page', $survey) }}" class="btn-teal w-full py-3 rounded-xl mt-5 text-center font-black uppercase text-[10px] tracking-widest shadow-lg block">
                                        Pay Final Balance Now
                                    </a>
                                @endif
                            </div>
                            @else
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <p class="text-[10px] font-black text-amber-500 uppercase flex items-center gap-2">
                                    <span class="animate-pulse">●</span> Awaiting Site Visit
                                </p>
                                <p class="text-xs text-white/50 font-bold mt-1">Final price will be confirmed by the surveyor during/after the site visit.</p>
                            </div>
                            @endif
                        </div>
                    @elseif($survey->payment_status === 'fully_paid')
                        <div class="bg-emerald-500/10 border border-emerald-500/30 p-5 rounded-2xl text-center">
                            <div class="w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-xs font-black text-emerald-500 uppercase">Payment Completed</p>
                            <p class="text-[9px] text-white/40 mt-1 mb-4 uppercase font-bold">Thank you for choosing Smart Survey</p>
                            <a href="{{ route('survey.invoice', $survey) }}" target="_blank" class="flex items-center justify-center gap-2 btn-teal w-full py-2.5 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Download Invoice
                            </a>
                        </div>
                    @endif

                    @if($survey->scheduled_at && $survey->schedule_status === 'accepted')
                        <div class="bg-white/5 border border-white/10 p-5 rounded-2xl">
                            <label class="text-[10px] font-black text-deepteal uppercase tracking-widest block mb-2">Confirmed Field Visit</label>
                            <div class="text-white font-bold">{{ $survey->scheduled_at->format('M d, Y') }}</div>
                            <div class="text-gray-400 text-xs">{{ $survey->scheduled_at->format('h:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- New: Sticky Alert for Pending Balance --}}
            @if(in_array($survey->payment_status, ['pending', 'advance_paid']) && $survey->status !== 'requested')
                @php
                    $isPendingVerification = $survey->payment_slip_path && (
                        ($survey->payment_status === 'pending' && $survey->payment_slip_type === 'advance') ||
                        ($survey->payment_status === 'advance_paid' && $survey->payment_slip_type === 'balance')
                    );
                @endphp
                @if(!$isPendingVerification)
                <div class="bg-amber-500/10 border border-amber-500/20 p-6 rounded-3xl">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-10 h-10 bg-amber-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-500/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-amber-500 uppercase tracking-[0.2em]">Pending Action</p>
                            <h4 class="text-white font-black">Financial Settle Required</h4>
                        </div>
                    </div>
                    <p class="text-xs text-white/60 font-medium mb-5 leading-relaxed">
                        To maintain progress on your project in <b>{{ $survey->village }}</b>, please settle the outstanding 
                        {{ $survey->payment_status === 'pending' ? 'advance' : 'balance' }} amount.
                    </p>
                    <a href="{{ route('payment.page', $survey) }}" class="flex items-center justify-center w-full bg-amber-500 py-3 rounded-2xl text-white font-black text-xs uppercase tracking-widest shadow-xl shadow-amber-500/20 hover:scale-[1.02] transition-transform">
                        Proceed to Payment Portal &rarr;
                    </a>
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const map = L.map('map', { 
        zoomControl: false,
        attributionControl: false 
    }).setView([{{ $survey->location_lat ?? 7.8731 }}, {{ $survey->location_lng ?? 80.7718 }}], 14);
    
    L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png').addTo(map);
    
    @if($survey->location_lat)
        L.marker([{{ $survey->location_lat }}, {{ $survey->location_lng }}]).addTo(map);
    @endif
</script>
@endpush
