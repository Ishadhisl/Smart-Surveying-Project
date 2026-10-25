@extends('layouts.admin')

@section('admin-content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tighter">Job Management</h1>
        <p class="text-white font-bold opacity-60 tracking-wide uppercase text-[10px] mt-1">All Survey Projects & Field Operations</p>
    </div>
    <div class="flex items-center gap-3">
        <form method="GET" class="flex items-center gap-2">
            <div class="relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    class="pl-9 pr-4 py-2.5 bg-white/40 border border-white/40 rounded-2xl text-sm font-semibold focus:outline-none focus:bg-white/70 focus:border-deepteal transition-all w-52 text-slate-900 placeholder-slate-500"
                    placeholder="Search village or client...">
            </div>
            <button type="submit" class="btn-teal p-2.5 rounded-2xl shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
        <form method="GET">
            <input type="hidden" name="search" value="{{ request('search') }}">
            <select name="sort" onchange="this.form.submit()"
                class="bg-white/40 border border-white/40 rounded-2xl text-sm font-semibold px-4 py-2.5 focus:outline-none focus:bg-white/70 text-slate-900 cursor-pointer">
                <option value="newest" {{ request('sort','newest')=='newest'?'selected':'' }}>Newest</option>
                <option value="oldest" {{ request('sort')=='oldest'?'selected':'' }}>Oldest</option>
                <option value="status" {{ request('sort')=='status'?'selected':'' }}>By Status</option>
            </select>
        </form>
    </div>
</div>

{{-- ====== JOBS TABLE ====== --}}
<div class="glass rounded-[2rem] shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="w-16">ID</th>
                    <th>Client</th>
                    <th>Village</th>
                    <th>Details & Financials</th>
                    <th>Status</th>
                    <th>Date/Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                <tr>
                    {{-- ID --}}
                    <td>
                        <span class="font-black text-slate-500">#{{ $project->id }}</span>
                    </td>

                    {{-- Client --}}
                    <td>
                        <p class="font-black text-slate-900 text-sm">{{ $project->user->name }}</p>
                        @php
                            $pNumber = preg_replace('/[^0-9]/', '', $project->user->whatsapp_number);
                            $formattedPhone = str_starts_with($pNumber, '0') ? '94' . substr($pNumber, 1) : (str_starts_with($pNumber, '94') ? $pNumber : '94' . $pNumber);
                        @endphp
                        <a href="https://wa.me/{{ $formattedPhone }}"
                            target="_blank"
                            class="text-xs text-deepteal font-bold hover:underline">
                            {{ $project->user->whatsapp_number }}
                        </a>
                    </td>

                    {{-- Village --}}
                    <td>
                        <p class="font-bold text-slate-800 text-sm">{{ $project->village }}</p>
                    </td>

                    {{-- Details & Financials --}}
                    <td class="max-w-[210px]">
                        <p class="font-black text-deepteal text-sm mb-1">
                            {{ ucwords(str_replace('_', ' ', $project->purpose)) }}
                            @if($project->other_purpose)
                                <span class="text-slate-500 font-semibold">({{ $project->other_purpose }})</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-600 font-semibold mb-1.5 break-words max-w-[200px]">
                            @if($project->size_acres > 0)
                                {{ $project->size_acres + 0 }} Acres,
                            @endif
                            {{ $project->size_perches + 0 }} Perches
                            | {{ $project->boundary_points }} Points
                            @if($project->lot_count > 1) | {{ $project->lot_count }} Lots @endif
                        </p>
                        <div class="space-y-1">
                            <p class="text-xs text-slate-500">
                                Est Fee: <span class="font-black text-slate-800">Rs. {{ number_format($project->estimate_price, 2) }}</span>
                            </p>
                            @if($project->actual_price)
                            <p class="text-xs text-slate-500">
                                Final: <span class="font-black text-emerald-700">Rs. {{ number_format($project->actual_price, 2) }}</span>
                            </p>
                            @endif
                            @if(in_array($project->payment_status, ['advance_paid','fully_paid']))
                            <span class="inline-block text-[10px] bg-blue-100/80 text-blue-700 font-black px-2 py-0.5 rounded-full">
                                Advance: Rs. {{ number_format($project->estimate_price / 3, 2) }}
                            </span>
                            @endif
                            @if($project->balance_amount > 0 && $project->payment_status !== 'fully_paid')
                            <span class="inline-block text-[10px] bg-amber-100/80 text-amber-700 font-black px-2 py-0.5 rounded-full">
                                Balance: Rs. {{ number_format($project->balance_amount, 2) }}
                            </span>
                            @endif
                            @if($project->payment_status === 'fully_paid')
                            <span class="inline-block text-[10px] bg-emerald-100/80 text-emerald-700 font-black px-2 py-0.5 rounded-full">
                                ✓ Fully Paid
                            </span>
                            @endif
                        </div>
                    </td>

                    {{-- Status --}}
                    <td>
                        <div class="flex flex-col gap-1.5">
                            <span class="badge badge-{{ $project->status }}">
                                {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                            </span>
                            @if($project->payment_status !== 'pending')
                            <span class="badge badge-{{ $project->payment_status }}">
                                {{ ucfirst(str_replace('_', ' ', $project->payment_status)) }}
                            </span>
                            @endif
                        </div>
                    </td>

                    {{-- Date/Time --}}
                    <td>
                        @if($project->scheduled_at)
                            <p class="text-sm font-bold text-slate-900">{{ $project->scheduled_at->format('M d, y') }}</p>
                            <p class="text-xs text-slate-500 font-semibold">{{ $project->scheduled_at->format('g:i A') }}</p>
                            @if($project->scheduled_at->isToday())
                            <span class="inline-block text-[9px] bg-red-100/90 text-red-600 font-black px-2 py-0.5 rounded-full mt-1">TODAY</span>
                            @endif
                        @else
                            <span class="text-xs text-slate-400 font-bold">Not Set</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div class="flex flex-col gap-2">
                            
                            {{-- 1. Primary Action: Update --}}
                            <button type="button"
                                onclick="openUpdateModal({{ $project->id }}, '{{ $project->status }}', '{{ $project->payment_status }}', {{ $project->estimate_price }}, {{ $project->actual_price ?? 'null' }}, '{{ addslashes($project->user->name) }}', '{{ $project->village }}', {{ $project->location_lat ?? 'null' }}, {{ $project->location_lng ?? 'null' }})"
                                class="action-btn abtn-teal justify-center w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Update Status
                            </button>

                            {{-- 2. Contextual Workflow Actions --}}
                            @if(!$project->scheduled_at || $project->schedule_status === 'declined')
                            <button type="button"
                                onclick="openScheduleModal({{ $project->id }}, '{{ addslashes($project->user->name) }}', '{{ $project->village }}', {{ $project->location_lat ?? 'null' }}, {{ $project->location_lng ?? 'null' }})"
                                class="action-btn abtn-outline justify-center w-full text-[10px] py-1.5">
                                🗓️ Schedule Visit
                            </button>
                            @elseif($project->schedule_status === 'proposed')
                            <span class="action-btn justify-center text-center w-full py-1.5" style="background:rgba(254,243,199,0.7);color:#92400e;cursor:default;font-size:0.65rem;">
                                ⏳ Awaiting Client
                            </span>
                            @endif

                            @if($project->payment_status === 'pending' && $project->status !== 'requested')
                            <form action="{{ route('admin.payment.mark', $project) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="advance">
                                <button type="submit" class="action-btn abtn-orange justify-center w-full text-[10px] py-1.5">
                                    💰 Mark Advance
                                </button>
                            </form>
                            @elseif($project->payment_status === 'advance_paid' && $project->actual_price)
                            <form action="{{ route('admin.payment.mark', $project) }}" method="POST">
                                @csrf
                                <input type="hidden" name="type" value="full">
                                <button type="submit" class="action-btn abtn-teal justify-center w-full text-[10px] py-1.5">
                                    💰 Mark Final
                                </button>
                            </form>
                            @endif

                            <a href="{{ route('admin.cadastral', $project) }}"
                                class="action-btn justify-center w-full text-[10px] py-1.5 mt-1 border-none shadow-md inline-flex items-center gap-1 font-bold tracking-wider"
                                style="background: linear-gradient(135deg, #0d9488, #0f766e); color: white;" title="Open Cadastral Map Workspace">
                                🗺️ Cadastral Workspace
                            </a>

                            @if($project->purpose === 'partitioning')
                            <a href="{{ route('admin.subdivision', $project) }}"
                                class="action-btn justify-center w-full text-[10px] py-1 mt-1 border-none shadow-sm inline-flex items-center gap-1 font-bold tracking-wider bg-slate-800 text-white hover:bg-slate-900" title="Open Land Subdivision Studio">
                                ✂️ Subdivision Studio
                            </a>
                            @endif

                            @if(in_array($project->status, ['mapping', 'completed']))
                            <button type="button"
                                onclick="openBoundaryModal({{ $project->id }}, '{{ addslashes($project->user->name) }}', '{{ $project->village }}')"
                                class="action-btn justify-center w-full text-[10px] py-1 mt-1 text-slate-700 bg-white/40 hover:bg-white/60">
                                📍 Legacy AR Points
                            </button>
                            @endif

                            {{-- 3. Utility Icons Row (Location, Deed, Slip) --}}
                            <div class="flex items-center justify-center gap-2 mt-1">
                                @if($project->location_lat)
                                <button type="button" onclick="getLiveDirections(this, {{ $project->location_lat }}, {{ $project->location_lng }})"
                                    class="w-8 h-8 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 flex items-center justify-center transition-colors border-none cursor-pointer" title="View Location">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </button>
                                @endif

                                @if($project->deed_path)
                                <a href="{{ asset('storage/'.$project->deed_path) }}" target="_blank" 
                                    class="w-8 h-8 rounded-xl bg-slate-500/10 hover:bg-slate-500/20 text-slate-700 flex items-center justify-center transition-colors" title="View Deed">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </a>
                                @endif

                                @if($project->payment_slip_path)
                                <a href="{{ asset('storage/'.$project->payment_slip_path) }}" target="_blank" 
                                    class="w-8 h-8 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 flex items-center justify-center transition-colors border border-amber-500/20" title="View Payment Slip ({{ ucfirst($project->payment_slip_type) }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                                </a>
                                @endif
                            </div>

                            {{-- 4. WhatsApp Notifications (Compact) --}}
                            <div class="mt-1 pt-2 border-t border-black/5 text-center">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-[0.1em] mb-1.5">WhatsApp Notifier</p>
                                <div class="flex items-center justify-center gap-1.5">
                                    @php
                                        $pNumArr = preg_replace('/[^0-9]/', '', $project->user->whatsapp_number);
                                        $waPhoneFinal = str_starts_with($pNumArr, '0') ? '94' . substr($pNumArr, 1) : (str_starts_with($pNumArr, '94') ? $pNumArr : '94' . $pNumArr);
                                        
                                        $confMsg = "Hi {$project->user->name}, your site visit for {$project->village} is confirmed on " . ($project->scheduled_at?->format('M d, Y h:i A') ?? 'TBC') . ". Please be available.";
                                        $weatherMsg = "Hi {$project->user->name}, due to bad weather we need to reschedule your site visit at {$project->village}. Sorry for the inconvenience.";
                                        $priceMsg = "Hi {$project->user->name}, the final price for your survey in {$project->village} is set at Rs. " . number_format($project->actual_price) . ". Remaining balance is Rs. " . number_format($project->balance_amount) . ".";
                                    @endphp
                                    
                                    @if($project->schedule_status === 'accepted')
                                    <a href="https://wa.me/{{ $waPhoneFinal }}?text={{ urlencode($confMsg) }}" target="_blank" 
                                        class="px-2.5 py-1 rounded-lg bg-[#25D366]/10 text-[#25D366] text-[11px] font-bold hover:bg-[#25D366]/20 transition-colors" title="Confirm Visit">
                                        📅
                                    </a>
                                    @endif

                                    <a href="https://wa.me/{{ $waPhoneFinal }}?text={{ urlencode($weatherMsg) }}" target="_blank" 
                                        class="px-2.5 py-1 rounded-lg bg-orange-500/10 text-orange-600 text-[11px] font-bold hover:bg-orange-500/20 transition-colors" title="Weather Alert">
                                        ☁️
                                    </a>

                                    @if($project->actual_price)
                                    <a href="https://wa.me/{{ $waPhoneFinal }}?text={{ urlencode($priceMsg) }}" target="_blank" 
                                        class="px-2.5 py-1 rounded-lg bg-deepteal/10 text-deepteal text-[11px] font-bold hover:bg-deepteal/20 transition-colors" title="Notify Price">
                                        💰
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-20">
                        <svg class="w-12 h-12 text-white/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-white/40 font-black uppercase tracking-widest text-sm">No projects found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($projects->hasPages())
    <div class="px-6 py-4 border-t border-white/20">
        {{ $projects->withQueryString()->links() }}
    </div>
    @endif
</div>


{{-- ====== UPDATE STATUS MODAL ====== --}}
<div id="updateModal" class="modal-overlay">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-extrabold text-slate-900">Update Project</h3>
            <button onclick="closeModal('updateModal')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="glass rounded-2xl p-3 mb-5">
            <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Project</p>
            <p class="font-black text-slate-800 mt-0.5" id="modal-project-name">—</p>
        </div>

        <form id="updateForm" method="POST">
            @csrf

            <div class="mb-4">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Project Status</label>
                <select name="status" id="modal-status" class="admin-input">
                    <option value="requested">Requested (New)</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="site_visit">Site Visit Done</option>
                    <option value="mapping">Mapping In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="mb-4" id="price-input-group" style="display: none;">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">
                    Actual Final Price (Rs.)
                </label>
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">Rs.</span>
                    <input type="number" name="actual_price" id="modal-actual-price"
                        class="admin-input pl-10"
                        placeholder="e.g. 85000" min="0" step="any">
                </div>
                <p class="text-[10px] text-slate-400 mt-1.5 font-semibold">Leave blank to keep current estimate.</p>
            </div>

            <div class="mb-5 border-t border-slate-100 pt-4" id="date-input-group">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">
                    Propose/Reschedule Site Visit
                </label>
                <input type="datetime-local" name="proposed_at" id="modal-proposed-at"
                    class="admin-input date-weather-trigger"
                    min="{{ now()->format('Y-m-d\TH:i') }}">
                <p class="text-[10px] text-amber-600 mt-1.5 font-bold">Setting a date will notify the client to confirm.</p>
                <div id="update-weather-preview" class="mt-3 hidden bg-white/40 border border-white/50 rounded-xl p-3 flex items-center gap-3 transition-all"></div>
            </div>

            <button type="submit" class="w-full btn-teal py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg">
                Save Changes
            </button>
        </form>
    </div>
</div>


{{-- ====== SCHEDULE MODAL WITH 7-DAY WEATHER PLANNER ====== --}}
<div id="scheduleModal" class="modal-overlay">
    <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-extrabold text-slate-900">Propose Site Visit</h3>
                <p class="text-xs text-slate-500 font-medium">Plan the field survey with real-time meteorological risk scoring</p>
            </div>
            <button onclick="closeModal('scheduleModal')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="glass rounded-2xl p-3 mb-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-widest">Client Land / Project</p>
                <p class="font-black text-slate-800 text-sm mt-0.5" id="schedule-project-name">—</p>
            </div>
            <div id="strip-loading-indicator" class="text-xs font-bold text-deepteal flex items-center gap-1.5 hidden">
                <span class="animate-spin text-base">⏳</span> Fetching forecast...
            </div>
        </div>

        {{-- 7-Day Forecast Strip --}}
        <div class="mb-5">
            <div class="flex items-center justify-between mb-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                    <span>🌦️ 7-Day Weather Risk Advisor</span>
                    <span class="text-[9px] font-bold text-slate-400 normal-case">(Click any day to select)</span>
                </label>
                <span class="text-[9px] font-bold text-deepteal bg-deepteal/10 px-2 py-0.5 rounded-full" id="strip-best-count">Top 3 Recommended</span>
            </div>

            <div id="schedule-weather-strip" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                {{-- Populated dynamically via JS --}}
                <div class="col-span-full py-6 text-center text-xs text-slate-400 font-semibold bg-slate-50 rounded-2xl border border-dashed border-slate-200">
                    Open schedule to load 7-day meteorological forecast.
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between mt-2 pt-2 border-t border-slate-100 text-[10px] text-slate-400 font-medium">
                <span>⚠️ <em>Advisory planning defaults, not legal regulations. Licensed surveyor makes the final visit decision.</em></span>
                <span class="mt-1 sm:mt-0 font-semibold">Forecast: Open-Meteo.com (CC BY 4.0)</span>
            </div>
        </div>

        <form id="scheduleForm" method="POST">
            @csrf
            <div class="mb-5">
                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Selected Visit Date & Time</label>
                <input type="datetime-local" name="proposed_at" id="schedule-proposed-at"
                    class="admin-input date-weather-trigger"
                    min="{{ now()->format('Y-m-d\TH:i') }}" required>
                <div id="schedule-weather-preview" class="mt-3 hidden bg-white/60 border border-slate-200 rounded-2xl p-3 flex items-center gap-3 transition-all shadow-sm"></div>
            </div>
            <button type="submit" class="w-full btn-teal py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg">
                Send Proposal to Client
            </button>
        </form>
    </div>
</div>

{{-- ====== BOUNDARY POINTS MODAL ====== --}}
<div id="boundaryModal" class="modal-overlay">
    <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-extrabold text-slate-900">Set AR Boundary Points</h3>
            <button onclick="closeModal('boundaryModal')" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="glass rounded-2xl p-3 mb-5 bg-deepteal/10 border-deepteal/20">
            <p class="text-[10px] text-deepteal font-black uppercase tracking-widest">Client Land</p>
            <p class="font-black text-slate-800 mt-0.5" id="boundary-project-name">—</p>
        </div>

        <form id="boundaryForm" method="POST">
            @csrf
            
            <div class="mb-4 flex items-center justify-between">
                <p class="text-xs font-bold text-slate-500">Add GPS Coordinates (Lat, Lng) for each corner of the land.</p>
                <button type="button" onclick="addBoundaryPoint()" class="bg-slate-200 text-slate-700 hover:bg-slate-300 px-3 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-widest transition">
                    + Add Point
                </button>
            </div>

            <div id="boundary-points-container" class="space-y-3 mb-6 max-h-[300px] overflow-y-auto pr-2">
                <!-- Points will be added here via JS -->
            </div>

            <button type="submit" class="w-full btn-teal py-3.5 rounded-2xl font-black text-sm uppercase tracking-widest shadow-lg" style="background: linear-gradient(135deg, #0d9488, #0f766e);">
                Save Boundaries for AR
            </button>
        </form>
    </div>
</div>

@endsection

@push('admin-scripts')
<script>
    let currentProjectLat = null;
    let currentProjectLng = null;

    function openUpdateModal(id, status, payStatus, estimate, actual, clientName, village, lat = null, lng = null) {
        currentProjectLat = lat;
        currentProjectLng = lng;
        document.getElementById('modal-project-name').textContent = '#' + id + ' — ' + clientName + ' (' + village + ')';
        document.getElementById('modal-status').value = status;
        document.getElementById('modal-actual-price').value = actual || '';
        document.getElementById('modal-proposed-at').value = '';
        document.getElementById('update-weather-preview').classList.add('hidden');
        document.getElementById('update-weather-preview').innerHTML = '';
        document.getElementById('updateForm').action = '/admin/survey/' + id + '/update-status';
        
        toggleDynamicFields(status); // Show or hide fields based on current status
        
        document.getElementById('updateModal').classList.add('show');
    }

    // Toggle price and date fields based on selected status
    function toggleDynamicFields(status) {
        const priceGroup = document.getElementById('price-input-group');
        const dateGroup = document.getElementById('date-input-group');
        
        // Final Price is only relevant after the site visit is done
        if (['site_visit', 'mapping', 'completed'].includes(status)) {
            priceGroup.style.display = 'block';
            dateGroup.style.display = 'none'; // No need to schedule if it's already done
        } else {
            priceGroup.style.display = 'none';
            dateGroup.style.display = 'block'; // Allow scheduling/rescheduling
        }
    }

    // Listen to status dropdown changes
    document.getElementById('modal-status').addEventListener('change', function(e) {
        toggleDynamicFields(e.target.value);
    });


    let currentSurveyId = null;

    function openScheduleModal(id, clientName, village, lat = null, lng = null) {
        currentSurveyId = id;
        currentProjectLat = lat;
        currentProjectLng = lng;
        document.getElementById('schedule-project-name').textContent = '#' + id + ' — ' + clientName + ' (' + village + ')';
        document.getElementById('schedule-proposed-at').value = '';
        document.getElementById('schedule-weather-preview').classList.add('hidden');
        document.getElementById('schedule-weather-preview').innerHTML = '';
        document.getElementById('scheduleForm').action = '/admin/survey/' + id + '/propose-schedule';
        document.getElementById('scheduleModal').classList.add('show');

        // Fetch 7-day forecast strip
        loadWeatherStrip(id, lat, lng);
    }

    async function loadWeatherStrip(surveyId, lat, lng) {
        const stripContainer = document.getElementById('schedule-weather-strip');
        const loader = document.getElementById('strip-loading-indicator');
        
        loader.classList.remove('hidden');
        stripContainer.innerHTML = `
            <div class="col-span-full py-6 text-center text-xs text-slate-500 font-semibold bg-slate-50 rounded-2xl border border-dashed border-slate-200 animate-pulse">
                ⏳ Loading 7-day weather assessment...
            </div>
        `;

        try {
            const url = `/admin/survey/${surveyId}/weather-forecast` + (lat && lng ? `?lat=${lat}&lng=${lng}` : '');
            const response = await fetch(url);
            loader.classList.add('hidden');

            if (!response.ok) throw new Error('Forecast unavailable');
            const data = await response.json();

            if (!data.per_day || data.per_day.length === 0) {
                stripContainer.innerHTML = `
                    <div class="col-span-full py-4 text-center text-xs text-slate-400 font-medium">
                        Forecast unavailable for this location.
                    </div>
                `;
                return;
            }

            let html = '';
            data.per_day.forEach(day => {
                const isBest = (data.best_days_in_order || []).includes(day.date);
                let riskClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                let riskBadge = 'Low Risk';

                if (day.risk === 'High') {
                    riskClass = 'bg-rose-50 text-rose-700 border-rose-200';
                    riskBadge = 'High Risk';
                } else if (day.risk === 'Medium') {
                    riskClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    riskBadge = 'Medium';
                }

                const dayDateObj = new Date(day.date + 'T00:00:00');
                const dayShort = dayDateObj.toLocaleDateString('en-US', { weekday: 'short' });
                const dateShort = dayDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                html += `
                    <div onclick="selectForecastDate('${day.date}', this)"
                         class="weather-day-card cursor-pointer border rounded-2xl p-2.5 transition-all text-center hover:scale-105 hover:shadow-md bg-white border-slate-200 relative">
                        ${isBest ? `<span class="absolute -top-2 left-1/2 -translate-x-1/2 bg-deepteal text-white text-[8px] font-black px-1.5 py-0.2 rounded-full uppercase tracking-tighter whitespace-nowrap shadow-sm">★ Top Pick</span>` : ''}
                        
                        <div class="text-[11px] font-black text-slate-800 leading-tight mt-0.5">${dayShort}</div>
                        <div class="text-[9px] font-bold text-slate-400 mb-1">${dateShort}</div>
                        
                        <div class="text-2xl my-1 drop-shadow-sm">${day.icon || '⛅'}</div>
                        <div class="text-xs font-black text-slate-800">${Math.round(day.temperature_2m_max)}°C</div>
                        
                        <div class="mt-1 text-[8px] font-bold px-1.5 py-0.5 rounded-full border ${riskClass}">
                            ${riskBadge}
                        </div>

                        <div class="mt-1 text-[8px] text-slate-400 font-semibold space-y-0.5 leading-tight">
                            <div>💧 ${day.precipitation_sum}mm</div>
                            <div>💨 ${Math.round(day.wind_speed_10m_max)}km/h</div>
                        </div>
                    </div>
                `;
            });

            stripContainer.innerHTML = html;

        } catch (err) {
            loader.classList.add('hidden');
            stripContainer.innerHTML = `
                <div class="col-span-full py-4 text-center text-xs text-slate-400 font-medium">
                    Weather forecast currently unavailable. You may manually select a visit date below.
                </div>
            `;
        }
    }

    function selectForecastDate(dateStr, cardElement) {
        // Highlight chosen card
        document.querySelectorAll('.weather-day-card').forEach(c => {
            c.classList.remove('ring-2', 'ring-deepteal', 'border-deepteal', 'bg-deepteal/5');
            c.classList.add('bg-white', 'border-slate-200');
        });
        if (cardElement) {
            cardElement.classList.add('ring-2', 'ring-deepteal', 'border-deepteal', 'bg-deepteal/5');
            cardElement.classList.remove('border-slate-200', 'bg-white');
        }

        const dateInput = document.getElementById('schedule-proposed-at');
        // Default to morning visit at 09:00 AM
        dateInput.value = `${dateStr}T09:00`;
        dateInput.dispatchEvent(new Event('change'));
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
    }

    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    });

    // Weather Fetch Logic
    document.querySelectorAll('.date-weather-trigger').forEach(input => {
        input.addEventListener('change', async function() {
            const dateVal = this.value;
            const isUpdateModal = this.id === 'modal-proposed-at';
            const previewDiv = document.getElementById(isUpdateModal ? 'update-weather-preview' : 'schedule-weather-preview');

            if (!dateVal || !currentProjectLat || !currentProjectLng) {
                previewDiv.classList.add('hidden');
                return;
            }

            previewDiv.innerHTML = '<span class="text-xs font-bold text-slate-500">⏳ Fetching weather assessment...</span>';
            previewDiv.classList.remove('hidden');

            try {
                const response = await fetch(`/admin/weather-check?lat=${currentProjectLat}&lng=${currentProjectLng}&date=${dateVal}`);
                if (!response.ok) throw new Error('Network response was not ok');
                const weather = await response.json();
                
                if (weather.error) {
                    previewDiv.innerHTML = `<span class="text-xs font-bold text-slate-500">Weather data unavailable</span>`;
                    return;
                }

                if (weather.status === 'unavailable') {
                    previewDiv.innerHTML = `
                        <div class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 p-2 rounded-xl w-full">
                            ℹ️ ${weather.message}
                        </div>
                    `;
                    return;
                }

                let badgeColor = 'bg-emerald-100 text-emerald-800 border-emerald-300';
                if (weather.risk === 'High') {
                    badgeColor = 'bg-rose-100 text-rose-800 border-rose-300';
                } else if (weather.risk === 'Medium') {
                    badgeColor = 'bg-amber-100 text-amber-800 border-amber-300';
                }

                const reasonsHtml = (weather.reasons && weather.reasons.length > 0)
                    ? `<p class="text-[9px] text-slate-500 font-semibold mt-0.5">${weather.reasons[0]}</p>`
                    : '';

                previewDiv.innerHTML = `
                    <span class="text-3xl drop-shadow-sm">${weather.icon || '⛅'}</span>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-900">${weather.temp}°C</span>
                            <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-md border ${badgeColor}">
                                ${weather.risk || 'Normal'} (${weather.score || 80}/100)
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-600 font-extrabold uppercase tracking-wide mt-0.5">
                            ${weather.description || 'Forecast available'} · 💧 ${weather.precipitation_sum || 0}mm · 💨 ${weather.wind_speed_10m_max || 0}km/h
                        </p>
                        ${reasonsHtml}
                    </div>
                `;
            } catch (error) {
                previewDiv.innerHTML = `<span class="text-xs font-bold text-red-500">Failed to load weather</span>`;
            }
        });
    });

    // Boundary Modal Logic
    let pointCounter = 0;
    
    function openBoundaryModal(id, clientName, village) {
        document.getElementById('boundary-project-name').textContent = '#' + id + ' — ' + clientName + ' (' + village + ')';
        document.getElementById('boundaryForm').action = '/admin/survey/' + id + '/boundary-points';
        document.getElementById('boundary-points-container').innerHTML = '';
        pointCounter = 0;
        
        // Add 4 default points to start
        for(let i=0; i<4; i++) addBoundaryPoint();
        
        document.getElementById('boundaryModal').classList.add('show');
    }

    function addBoundaryPoint() {
        const container = document.getElementById('boundary-points-container');
        const pointDiv = document.createElement('div');
        pointDiv.className = 'flex gap-2 items-center bg-slate-50 p-2.5 rounded-xl border border-slate-200';
        pointDiv.innerHTML = `
            <div class="w-6 h-6 flex items-center justify-center bg-slate-200 rounded-full text-[10px] font-black text-slate-500">\${pointCounter + 1}</div>
            <div class="flex-1">
                <input type="number" step="any" name="points[\${pointCounter}][latitude]" placeholder="Latitude (e.g. 6.9271)" class="w-full text-xs p-2 rounded-lg border border-slate-200 focus:outline-none focus:border-deepteal" required>
            </div>
            <div class="flex-1">
                <input type="number" step="any" name="points[\${pointCounter}][longitude]" placeholder="Longitude (e.g. 79.8612)" class="w-full text-xs p-2 rounded-lg border border-slate-200 focus:outline-none focus:border-deepteal" required>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="p-2 text-red-400 hover:text-red-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;
        container.appendChild(pointDiv);
        pointCounter++;
    }
</script>
@endpush
