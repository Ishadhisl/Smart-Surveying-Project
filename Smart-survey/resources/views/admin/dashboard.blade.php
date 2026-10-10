@extends('layouts.admin')

@section('admin-content')

{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tighter">Surveyor Dashboard</h1>
        <p class="text-white font-bold opacity-60 tracking-wide uppercase text-[10px] mt-1">Real-time Project Intelligence & Field Operations</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="glass px-4 py-2.5 rounded-2xl text-center shadow-md">
            <div class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Today</div>
            <div class="text-sm font-black text-slate-900">{{ now()->format('d M Y') }}</div>
        </div>
    </div>
</div>

{{-- ====== STATS ====== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

    <div class="stat-glass p-6 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Total Projects</p>
            <p class="text-4xl font-black text-slate-900">{{ $stats['total'] }}</p>
        </div>
        <div class="w-11 h-11 bg-deepteal/10 rounded-2xl flex items-center justify-center">
            <svg class="w-5 h-5 text-deepteal" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
    </div>

    <div class="stat-glass p-6 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Ongoing Jobs</p>
            <p class="text-4xl font-black text-blue-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="w-11 h-11 bg-blue-500/10 rounded-2xl flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
        </div>
    </div>

    <div class="stat-glass p-6 flex items-center justify-between">
        <div>
            <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">Completed</p>
            <p class="text-4xl font-black text-emerald-600">{{ $stats['completed'] }}</p>
        </div>
        <div class="w-11 h-11 bg-emerald-500/10 rounded-2xl flex items-center justify-center">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>

    <div class="bg-deepteal p-6 rounded-[1.5rem] shadow-xl flex items-center justify-between relative overflow-hidden">
        <div class="absolute -top-4 -right-4 w-16 h-16 bg-white/10 rounded-full blur-xl"></div>
        <div class="relative z-10">
            <p class="text-[10px] font-black text-white/50 uppercase tracking-widest mb-2">Monthly Revenue</p>
            <p class="text-2xl font-black text-white leading-tight">Rs. {{ number_format($stats['monthly_revenue'] / 1000) }}K</p>
            <p class="text-[9px] text-white/40 font-bold mt-1">This Month</p>
        </div>
        <div class="w-11 h-11 bg-white/15 rounded-2xl flex items-center justify-center relative z-10">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
    </div>
</div>

{{-- ====== MAIN GRID ====== --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: Visits + Revenue --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Declined Schedule Alert --}}
        @if(isset($declinedVisits) && $declinedVisits->count() > 0)
        <div class="bg-red-500/10 border border-red-500/20 p-6 rounded-[2rem] shadow-lg mb-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-red-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-red-500/40">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-white">Action Required: Reschedule Visits</h3>
                    <p class="text-xs text-red-200 mt-1">Following clients have declined the proposed schedule.</p>
                </div>
            </div>
            <div class="bg-white/5 rounded-2xl p-4 mt-2">
                @foreach($declinedVisits as $declined)
                <div class="flex items-center justify-between {{ !$loop->last ? 'border-b border-red-500/10 pb-3 mb-3' : '' }}">
                    <div>
                        <p class="text-sm font-bold text-white">{{ $declined->user->name }} — {{ $declined->village }}</p>
                        <p class="text-[10px] text-red-300 tracking-wider">PROJECT #{{ $declined->id }}</p>
                    </div>
                    <a href="{{ route('admin.projects') }}" class="btn-glass px-4 py-2 rounded-xl text-[10px] font-black uppercase text-red-500 hover:bg-red-500/20">
                        Reschedule Now
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Site Visit Reminders --}}
        <div class="glass rounded-[2rem] p-8 shadow-xl">
            <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-3">
                <div class="w-8 h-8 bg-deepteal/10 rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-deepteal" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                    </svg>
                </div>
                Site Visit Reminders
            </h3>

            @if($upcomingVisits->count() > 0)
            <div class="space-y-3">
                @foreach($upcomingVisits as $visit)
                <div class="bg-white/30 border border-white/40 rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-center gap-4 hover:bg-white/45 transition-all shadow-sm">
                    <div>
                        <h4 class="font-black text-slate-900">#{{ $visit->id }} — {{ $visit->user->name }}</h4>
                        <p class="text-[10px] text-slate-600 font-bold uppercase tracking-widest mt-0.5">
                            {{ $visit->village }} • {{ $visit->user->whatsapp_number }}
                        </p>
                    </div>
                    <div class="flex items-center gap-4">
                        {{-- Weather Status --}}
                        @if(isset($visit->weather))
                        <div class="text-center px-3 py-1.5 rounded-xl bg-white/50 border border-white {{ $visit->weather['is_bad'] ? 'text-red-600' : 'text-slate-800' }}">
                            <div class="text-lg leading-none">{{ $visit->weather['icon'] }}</div>
                            <div class="text-[8px] font-black uppercase tracking-tighter mt-0.5">{{ $visit->weather['temp'] }}°C</div>
                        </div>
                        @endif

                        <div class="text-right">
                            <div class="text-sm font-black text-slate-900">{{ $visit->scheduled_at->format('h:i A') }}</div>
                            <div class="text-[9px] text-white font-black bg-deepteal px-2.5 py-0.5 rounded-full mt-1 w-max ml-auto">
                                {{ $visit->scheduled_at->isToday() ? 'TODAY' : $visit->scheduled_at->format('M d') }}
                            </div>
                        </div>
                        @if($visit->location_lat)
                        <button type="button" 
                            onclick="getLiveDirections(this, {{ $visit->location_lat }}, {{ $visit->location_lng }})"
                            class="w-10 h-10 rounded-xl bg-white shadow-md flex items-center justify-center text-deepteal hover:scale-110 transition-transform cursor-pointer border-none">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-16">
                <svg class="w-10 h-10 text-white/30 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-slate-900/30 text-sm font-black uppercase tracking-widest">No Pending Field Schedules</p>
            </div>
            @endif
        </div>

        {{-- Revenue Chart --}}
        <div class="glass rounded-[2rem] p-8 shadow-xl">
            <h3 class="text-lg font-black text-slate-900 mb-6">Professional Revenue Flow</h3>
            <div class="h-[260px]">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Right: Status + Weather + Actions --}}
    <div class="lg:col-span-1 space-y-6">

        {{-- Status Chart --}}
        <div class="glass rounded-[2rem] p-8 shadow-xl flex flex-col items-center">
            <h3 class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-6 self-start">Status Intelligence</h3>
            <div class="w-full h-52">
                <canvas id="statusChart"></canvas>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-3 w-full">
                <div class="bg-white/30 border border-white/40 p-3 rounded-2xl text-center">
                    <p class="text-[9px] text-slate-500 font-black uppercase">Done</p>
                    <p class="text-2xl font-black text-emerald-600">{{ $stats['completed'] }}</p>
                </div>
                <div class="bg-white/30 border border-white/40 p-3 rounded-2xl text-center">
                    <p class="text-[9px] text-slate-500 font-black uppercase">Active</p>
                    <p class="text-2xl font-black text-blue-600">{{ $stats['pending'] }}</p>
                </div>
            </div>
        </div>

        {{-- Colombo Weather --}}
        <div class="glass rounded-[2rem] p-6 bg-slate-900/60 shadow-xl">
            <h3 class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-4">Colombo Weather</h3>
            <div class="flex items-center gap-4">
                <span class="text-4xl">⛅</span>
                <div>
                    <p class="text-3xl font-black text-white">29.6°C</p>
                    <p class="text-[10px] text-white/50 font-bold mt-0.5">Clear Sky • Ideal for Field Work</p>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="glass rounded-[2rem] p-6 bg-slate-900/70 shadow-xl">
            <h3 class="text-[9px] font-black text-white/40 uppercase tracking-widest mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <a href="{{ url('/admin/projects') }}" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition text-white">
                    <div class="w-2 h-2 rounded-full bg-deepteal shadow-[0_0_8px_rgba(0,128,128,1)]"></div>
                    <span class="text-xs font-bold">View All Jobs</span>
                </a>
                <a href="{{ url('/admin/upcoming-visits') }}" class="flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 hover:bg-white/10 transition text-white">
                    <div class="w-2 h-2 rounded-full bg-orange-400 shadow-[0_0_8px_rgba(251,146,60,1)]"></div>
                    <span class="text-xs font-bold">Upcoming Visits</span>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('admin-scripts')
<script>
    Chart.defaults.color = '#1e293b';
    Chart.defaults.font.family = 'Inter';
    Chart.defaults.font.weight = 'bold';

    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','May','Jun'],
            datasets: [{
                label: 'Income (Rs)',
                data: [150000,200000,100000,300000,250000,455000],
                backgroundColor: '#008080',
                borderRadius: 12,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.06)' }, border: { display: false } },
                x: { grid: { display: false }, border: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Completed','Ongoing','Requested'],
            datasets: [{
                data: [{{ $stats['completed'] }}, {{ $stats['pending'] }}, {{ max(0, $stats['total'] - $stats['completed'] - $stats['pending']) }}],
                backgroundColor: ['#10b981','#3b82f6','#008080'],
                borderWidth: 0,
                spacing: 10
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '82%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
