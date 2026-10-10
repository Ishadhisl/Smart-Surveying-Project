<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin — {{ config('app.name', 'Smart Survey') }}</title>

    {{-- ✅ Same scripts as app.blade.php --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        deepteal: '#008080',
                        brandbg: '#94918b'
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* ============================
           ✅ COPIED FROM app.blade.php
           ============================ */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #94918b;
        }
        .glass {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .btn-teal {
            background: #008080;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-teal:hover {
            background: #006666;
            transform: translateY(-2px);
        }
        /* ============================
           ADMIN-SPECIFIC STYLES
           (extend — not override)
           ============================ */

        /* Sidebar nav links */
        .admin-nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.875rem;
            border-radius: 0.875rem;
            font-size: 0.8rem;
            font-weight: 700;
            color: rgba(30,41,59,0.6);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .admin-nav-item:hover {
            background: rgba(255,255,255,0.45);
            color: #008080;
        }
        .admin-nav-item.active {
            background: #008080;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(0,128,128,0.3);
        }

        /* Stat cards — glass variant */
        .stat-glass {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.35);
            border-radius: 1.5rem;
            transition: background 0.2s;
        }
        .stat-glass:hover { background: rgba(255,255,255,0.38); }

        /* Table */
        .admin-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .admin-table thead th {
            padding: 0.85rem 1.25rem;
            text-align: left;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: rgba(30,41,59,0.5);
            border-bottom: 1px solid rgba(255,255,255,0.3);
        }
        .admin-table tbody tr { transition: background 0.15s; }
        .admin-table tbody tr:hover { background: rgba(255,255,255,0.15); }
        .admin-table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            font-size: 0.85rem;
        }

        /* Status badges — glass style */
        .badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 999px;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }
        .badge-requested    { background: rgba(219,234,254,0.8); color: #1e40af; }
        .badge-confirmed    { background: rgba(209,250,229,0.8); color: #065f46; }
        .badge-pending      { background: rgba(254,243,199,0.8); color: #854d0e; }
        .badge-advance_paid { background: rgba(224,231,255,0.8); color: #3730a3; }
        .badge-site_visit   { background: rgba(237,233,254,0.8); color: #5b21b6; }
        .badge-mapping      { background: rgba(254,226,226,0.8); color: #991b1b; }
        .badge-completed    { background: rgba(209,250,229,0.9); color: #065f46; }
        .badge-cancelled    { background: rgba(254,202,202,0.8); color: #991b1b; }
        .badge-fully_paid   { background: rgba(209,250,229,0.9); color: #065f46; }
        .badge-proposed     { background: rgba(254,243,199,0.8); color: #92400e; }

        /* Action buttons — consistent with btn-teal */
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.85rem;
            border-radius: 0.625rem;
            font-size: 0.72rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .action-btn:hover { transform: translateY(-1px); }
        .abtn-teal   { background: #008080; color: #fff; box-shadow: 0 2px 8px rgba(0,128,128,0.25); }
        .abtn-teal:hover { background: #006666; }
        .abtn-blue   { background: rgba(59,130,246,0.85); color: #fff; box-shadow: 0 2px 8px rgba(59,130,246,0.2); }
        .abtn-blue:hover { background: #2563eb; }
        .abtn-dark   { background: rgba(30,41,59,0.85); color: #fff; }
        .abtn-dark:hover { background: #0f172a; }
        .abtn-orange { background: rgba(249,115,22,0.85); color: #fff; }
        .abtn-orange:hover { background: #ea6f10; }
        .abtn-outline { background: transparent; border: 2px solid #008080; color: #008080; }
        .abtn-outline:hover { background: #008080; color: #fff; }

        /* Modal overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(4px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 2rem;
            padding: 2rem;
            max-width: 500px;
            width: 92%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 30px 80px rgba(0,0,0,0.15);
        }

        /* Form inputs in admin */
        .admin-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 0.875rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #1e293b;
            transition: all 0.2s;
            outline: none;
        }
        .admin-input:focus {
            background: rgba(255,255,255,0.95);
            border-color: #008080;
            box-shadow: 0 0 0 3px rgba(0,128,128,0.1);
        }

        /* Flash alerts */
        .flash-success {
            background: rgba(209,250,229,0.9);
            border-left: 4px solid #008080;
            color: #065f46;
            backdrop-filter: blur(10px);
        }
        .flash-warning {
            background: rgba(254,243,199,0.9);
            border-left: 4px solid #f59e0b;
            color: #854d0e;
            backdrop-filter: blur(10px);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
    </style>
</head>
<body class="antialiased min-h-screen">

{{-- ============================
     TOP NAVBAR (same as app.blade.php)
     ============================ --}}
<nav class="sticky top-0 z-50 w-full px-8 py-3 glass border-b border-white/20">
    <div class="max-w-full mx-auto flex justify-between items-center">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <div class="p-2 bg-deepteal rounded-lg shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <div>
                <span class="text-slate-900 font-extrabold text-lg tracking-tighter block leading-tight">Smart Survey</span>
                <span class="text-white font-bold text-[9px] tracking-widest uppercase opacity-80">Precision First</span>
            </div>
        </a>

        {{-- Center nav --}}
        <div class="hidden md:flex items-center space-x-8">
            <a href="{{ url('/admin/dashboard') }}"
               class="text-sm font-bold transition-colors {{ request()->is('admin/dashboard') ? 'text-deepteal underline underline-offset-4' : 'text-slate-800 hover:text-deepteal' }}">
               Dashboard
            </a>
            <a href="{{ url('/admin/upcoming-visits') }}"
               class="text-sm font-bold transition-colors {{ request()->is('admin/upcoming-visits') ? 'text-deepteal underline underline-offset-4' : 'text-slate-800 hover:text-deepteal' }}">
               Upcoming Visits
            </a>
            <a href="{{ url('/admin/projects') }}"
               class="text-sm font-bold transition-colors {{ request()->is('admin/projects') ? 'text-deepteal underline underline-offset-4' : 'text-slate-800 hover:text-deepteal' }}">
               Job Management
            </a>
        </div>

        {{-- User info + logout --}}
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <div class="text-[9px] font-black text-white/50 uppercase tracking-widest leading-none mb-0.5">Surveyor</div>
                <div class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</div>
                <div class="text-[9px] font-bold text-deepteal">LS# {{ auth()->user()->license_number }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="p-2 hover:bg-white/20 rounded-full transition text-slate-800" title="Logout">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</nav>

{{-- ============================
     MAIN LAYOUT: SIDEBAR + CONTENT
     ============================ --}}
<div class="flex gap-6 max-w-[1400px] mx-auto px-6 py-8">

    {{-- ====== SIDEBAR ====== --}}
    <aside class="hidden lg:flex flex-col w-60 flex-shrink-0">
        <div class="glass rounded-[2rem] p-5 sticky top-24 shadow-xl overflow-hidden">

            {{-- Profile --}}
            <div class="flex flex-col items-center text-center pb-5 mb-5 border-b border-white/25">
                <div class="w-14 h-14 bg-gradient-to-br from-deepteal to-slate-700 rounded-2xl flex items-center justify-center shadow-lg mb-3">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <p class="font-extrabold text-slate-900 text-sm leading-tight">{{ auth()->user()->name }}</p>
                <span class="text-[9px] font-black text-deepteal uppercase tracking-widest bg-deepteal/10 px-2.5 py-0.5 rounded-full mt-1.5">
                    Licensed Surveyor
                </span>
                <p class="text-[9px] text-slate-500 font-bold mt-1.5">LS# {{ auth()->user()->license_number }}</p>
            </div>

            {{-- Nav Links --}}
            <nav class="space-y-1">
                <p class="text-[9px] font-black text-slate-400/80 uppercase tracking-[0.18em] px-2 mb-2">Main Menu</p>

                <a href="{{ url('/admin/dashboard') }}"
                   class="admin-nav-item {{ request()->is('admin/dashboard') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ url('/admin/upcoming-visits') }}"
                   class="admin-nav-item {{ request()->is('admin/upcoming-visits') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Upcoming Visits
                </a>

                <a href="{{ url('/admin/projects') }}"
                   class="admin-nav-item {{ request()->is('admin/projects') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Job Management
                </a>


                <p class="text-[9px] font-black text-slate-400/80 uppercase tracking-[0.18em] px-2 mt-5 mb-2">Management</p>

                <a href="{{ route('admin.clients') }}" class="admin-nav-item {{ request()->is('admin/clients') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Clients
                </a>

                <a href="{{ route('admin.deeds') }}" class="admin-nav-item {{ request()->is('admin/deeds') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Deeds
                </a>

                <a href="{{ route('admin.feedbacks') }}" class="admin-nav-item {{ request()->is('admin/feedbacks') ? 'active' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                    Feedbacks
                </a>
            </nav>

            {{-- Logout --}}
            <div class="mt-5 pt-4 border-t border-white/25">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50/40 transition-all duration-200 group">
                        <svg class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ====== PAGE CONTENT ====== --}}
    <div class="flex-1 min-w-0">

        {{-- Flash Messages --}}
        @if(session('success'))
        <div class="flash-success rounded-2xl p-4 mb-6 flex items-center gap-3 shadow-lg">
            <div class="bg-deepteal p-1 rounded-full flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
        @endif
        @if(session('warning'))
        <div class="flash-warning rounded-2xl p-4 mb-6 flex items-center gap-3 shadow-lg">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.962-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <span class="font-bold text-sm">{{ session('warning') }}</span>
        </div>
        @endif

        @yield('admin-content')
    </div>
</div>

{{-- Mobile Bottom Nav --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50 glass border-t border-white/20 px-4 py-2">
    <div class="flex justify-around items-center">
        <a href="{{ url('/admin/dashboard') }}" class="flex flex-col items-center gap-0.5 {{ request()->is('admin/dashboard') ? 'text-deepteal' : 'text-slate-600' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span class="text-[9px] font-black uppercase">Home</span>
        </a>
        <a href="{{ url('/admin/upcoming-visits') }}" class="flex flex-col items-center gap-0.5 {{ request()->is('admin/upcoming-visits') ? 'text-deepteal' : 'text-slate-600' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span class="text-[9px] font-black uppercase">Visits</span>
        </a>
        <a href="{{ url('/admin/projects') }}" class="flex flex-col items-center gap-0.5 {{ request()->is('admin/projects') ? 'text-deepteal' : 'text-slate-600' }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="text-[9px] font-black uppercase">Jobs</span>
        </a>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="flex flex-col items-center gap-0.5 text-red-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span class="text-[9px] font-black uppercase">Logout</span>
            </button>
        </form>
    </div>
</div>

<script>
    function getLiveDirections(btn, destLat, destLng) {
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<span class="text-[8px] font-black italic">Locating...</span>';
        
        // Open the window synchronously to bypass popup blockers
        const newWindow = window.open('', '_blank');
        
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const currentLat = position.coords.latitude;
                const currentLng = position.coords.longitude;
                const url = `https://www.google.com/maps/dir/?api=1&origin=${currentLat},${currentLng}&destination=${destLat},${destLng}`;
                newWindow.location.href = url;
                btn.innerHTML = originalContent;
            }, function(error) {
                const url = `https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}`;
                newWindow.location.href = url;
                btn.innerHTML = originalContent;
            }, { enableHighAccuracy: true, timeout: 5000 });
        } else {
            const url = `https://www.google.com/maps/dir/?api=1&destination=${destLat},${destLng}`;
            newWindow.location.href = url;
            btn.innerHTML = originalContent;
        }
    }
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@stack('admin-scripts')
</body>
</html>
