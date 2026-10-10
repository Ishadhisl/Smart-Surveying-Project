@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="p-2.5 bg-deepteal rounded-xl shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Active Sessions</h1>
                <p class="text-xs text-white/60 font-semibold">All devices where you are currently logged in</p>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="bg-white/90 border-l-4 border-deepteal text-slate-900 p-4 rounded-2xl mb-6 shadow-xl flex items-center gap-3">
        <div class="bg-deepteal p-1 rounded-full">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
        </div>
        <span class="font-bold text-sm">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Stats Bar --}}
    <div class="glass rounded-2xl p-5 mb-6 flex flex-wrap gap-6 items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-deepteal/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-deepteal" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <div class="text-[10px] font-black text-white/50 uppercase tracking-widest">Total Sessions</div>
                <div class="text-2xl font-extrabold text-slate-900">{{ $sessions->count() }}</div>
            </div>
        </div>

        {{-- Revoke All Others Button --}}
        @if($sessions->count() > 1)
        <form action="{{ route('sessions.destroyOthers') }}" method="POST"
              onsubmit="return confirm('This will log you out from all other devices. Continue?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="flex items-center gap-2 px-5 py-2.5 bg-red-500/90 hover:bg-red-600 text-white text-xs font-bold rounded-xl shadow-md transition-all hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout All Other Devices
            </button>
        </form>
        @endif
    </div>

    {{-- Sessions List --}}
    <div class="space-y-4">
        @forelse($sessions as $session)
        <div class="glass rounded-2xl p-5 transition-all hover:bg-white/30
            {{ $session->is_current ? 'border-2 border-deepteal/50 ring-2 ring-deepteal/10' : '' }}">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">

                {{-- Device Icon --}}
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center
                        {{ $session->is_current ? 'bg-deepteal' : 'bg-white/30' }}">
                        @if($session->device === 'Mobile')
                            <svg class="w-6 h-6 {{ $session->is_current ? 'text-white' : 'text-slate-700' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        @elseif($session->device === 'Tablet')
                            <svg class="w-6 h-6 {{ $session->is_current ? 'text-white' : 'text-slate-700' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        @else
                            <svg class="w-6 h-6 {{ $session->is_current ? 'text-white' : 'text-slate-700' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                </div>

                {{-- Session Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <span class="font-extrabold text-sm text-slate-900">{{ $session->browser }}</span>
                        @if($session->is_current)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-deepteal text-white text-[10px] font-black rounded-full uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-300 animate-pulse inline-block"></span>
                                Current Session
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600 font-semibold">
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $session->os }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/>
                            </svg>
                            {{ $session->ip_address ?? 'Unknown IP' }}
                        </span>
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Last active: {{ $session->last_active_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="mt-1 text-[10px] text-slate-400 font-mono truncate max-w-xs" title="{{ $session->user_agent }}">
                        {{ Str::limit($session->user_agent, 60) }}
                    </div>
                </div>

                {{-- Revoke Button --}}
                <div class="flex-shrink-0">
                    @if($session->is_current)
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 bg-red-100/70 hover:bg-red-200 text-red-600 text-xs font-bold rounded-xl transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    @else
                        <form action="{{ route('sessions.destroy', $session->id) }}" method="POST"
                              onsubmit="return confirm('Revoke this session?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="flex items-center gap-2 px-4 py-2 bg-white/40 hover:bg-red-100/70 hover:text-red-600 text-slate-600 text-xs font-bold rounded-xl transition-all border border-white/50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Revoke
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="glass rounded-2xl p-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <p class="text-sm font-bold text-slate-500">No active sessions found.</p>
        </div>
        @endforelse
    </div>

    {{-- Security Tips --}}
    <div class="glass rounded-2xl p-5 mt-6">
        <div class="flex items-center gap-2 mb-3">
            <svg class="w-4 h-4 text-deepteal" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-xs font-black text-slate-700 uppercase tracking-widest">Security Tips</span>
        </div>
        <ul class="space-y-1.5 text-xs text-slate-600 font-semibold">
            <li class="flex items-start gap-2">
                <span class="text-deepteal mt-0.5">•</span>
                If you see an unfamiliar device or location, revoke that session immediately.
            </li>
            <li class="flex items-start gap-2">
                <span class="text-deepteal mt-0.5">•</span>
                Always logout from shared or public computers.
            </li>
            <li class="flex items-start gap-2">
                <span class="text-deepteal mt-0.5">•</span>
                Sessions expire after {{ config('session.lifetime') }} minutes of inactivity.
            </li>
        </ul>
    </div>

</div>
@endsection
