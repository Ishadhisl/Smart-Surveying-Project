@extends('layouts.admin')

@section('admin-content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tighter">Client Directory</h1>
        <p class="text-white font-bold opacity-60 tracking-wide uppercase text-[10px] mt-1">Manage platform users</p>
    </div>

    <!-- Search -->
    <form action="{{ route('admin.clients') }}" method="GET" class="relative w-full sm:w-auto">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or phone..." 
               class="w-full sm:w-72 bg-white/40 border border-white/50 text-slate-900 text-sm font-bold rounded-2xl py-2.5 pl-10 pr-4 focus:ring-4 focus:ring-deepteal/20 focus:outline-none transition-all placeholder:text-slate-500">
        <svg class="w-4 h-4 text-slate-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
    </form>
</div>

{{-- PENDING SURVEYOR APPROVALS (PROFESSIONAL VERIFICATION) --}}
@if(isset($pendingSurveyors) && $pendingSurveyors->count() > 0)
<div class="mb-10">
    <div class="flex items-center gap-2 mb-3">
        <span class="p-1.5 bg-amber-500 text-white rounded-lg shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </span>
        <h2 class="text-lg font-black text-slate-900 tracking-tight">Pending Surveyor Applications</h2>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800">{{ $pendingSurveyors->count() }} Pending</span>
    </div>

    <div class="glass rounded-[2rem] overflow-hidden shadow-xl border border-amber-300/40 bg-white/70">
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Surveyor Name</th>
                        <th>License Number</th>
                        <th>Contact Email / WhatsApp</th>
                        <th>Applied On</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingSurveyors as $surveyor)
                    <tr class="hover:bg-amber-50/40 transition-colors">
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-800 font-black flex items-center justify-center text-xs">
                                    {{ substr($surveyor->name, 0, 2) }}
                                </div>
                                <div>
                                    <span class="font-extrabold text-slate-900 block">{{ $surveyor->name }}</span>
                                    <span class="text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-md">Pending Verification</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="font-black text-slate-900 font-mono text-xs px-2.5 py-1 bg-white rounded-lg border border-slate-200">
                                {{ $surveyor->license_number }}
                            </span>
                        </td>
                        <td>
                            <div class="text-xs font-bold text-slate-700">{{ $surveyor->email }}</div>
                            <div class="text-[10px] text-slate-500 font-bold">{{ $surveyor->whatsapp_number }}</div>
                        </td>
                        <td>
                            <span class="text-xs font-bold text-slate-500">{{ $surveyor->created_at->format('M d, Y - H:i') }}</span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('admin.surveyors.approve', $surveyor->id) }}" method="POST" onsubmit="return confirm('Confirm verification and activate surveyor account for {{ $surveyor->name }}?');">
                                    @csrf
                                    <button type="submit" class="action-btn abtn-teal text-xs py-1.5 px-3">
                                        ✓ Approve License
                                    </button>
                                </form>
                                <form action="{{ route('admin.surveyors.reject', $surveyor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reject this application?');">
                                    @csrf
                                    <button type="submit" class="action-btn text-xs py-1.5 px-3 bg-red-100 text-red-700 hover:bg-red-200">
                                        ✕ Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="glass rounded-[2rem] overflow-hidden shadow-xl border border-white/40">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Contact Phone</th>
                    <th>Project Details (Type & Location)</th>
                    <th>Registered On</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td class="w-1/4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-deepteal/10 text-deepteal font-black flex items-center justify-center text-xs">
                                {{ substr($client->name, 0, 2) }}
                            </div>
                            <span class="font-extrabold text-slate-900">{{ $client->name }}</span>
                        </div>
                    </td>
                    <td class="w-1/4 font-bold text-slate-700">{{ $client->whatsapp_number }}</td>
                    <td class="w-1/3">
                        @if($client->surveys->count() > 0)
                            <div class="space-y-2">
                                @foreach($client->surveys as $survey)
                                    <div class="bg-slate-100/50 border border-slate-200/60 rounded-xl p-2.5">
                                        <p class="text-xs font-black text-slate-800 tracking-wide uppercase">{{ $survey->purpose == 'other' ? $survey->other_purpose : ucfirst(str_replace('_', ' ', $survey->purpose)) }}</p>
                                        <p class="text-[10px] text-slate-500 font-bold flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                                            {{ $survey->village }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-[10px] bg-slate-100 text-slate-500 font-bold px-2 py-1 rounded-md">No Active Projects</span>
                        @endif
                    </td>
                    <td class="w-1/6">
                        <span class="text-xs font-bold text-slate-500">{{ $client->created_at->format('M d, Y') }}</span>
                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="5" class="text-center py-12">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/30 text-white mb-3">
                            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <p class="text-slate-500 font-bold block">No clients found matching your search.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $clients->links('pagination::tailwind') }}
</div>

@endsection
