@extends('layouts.admin')

@section('admin-content')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tighter">Deed Repository</h1>
        <p class="text-white font-bold opacity-60 tracking-wide uppercase text-[10px] mt-1">Access client uploaded deeds</p>
    </div>

    <!-- Search -->
    <form action="{{ route('admin.deeds') }}" method="GET" class="relative w-full sm:w-auto">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search client or village..." 
               class="w-full sm:w-72 bg-white/40 border border-white/50 text-slate-900 text-sm font-bold rounded-2xl py-2.5 pl-10 pr-4 focus:ring-4 focus:ring-deepteal/20 focus:outline-none transition-all placeholder:text-slate-500">
        <svg class="w-4 h-4 text-slate-500 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
    </form>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($surveysWithDeeds as $project)
        <div class="glass p-6 rounded-[2rem] shadow-lg border border-white/40 flex flex-col justify-between hover:bg-white/30 transition-all">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <span class="text-[10px] bg-slate-800 text-white px-2.5 py-1 rounded-full font-black tracking-widest uppercase">
                        Project #{{ $project->id }}
                    </span>
                    <span class="text-xs text-slate-500 font-bold whitespace-nowrap">{{ $project->created_at->format('M d, y') }}</span>
                </div>
                <h3 class="text-lg font-black text-slate-900 leading-tight mb-1">{{ $project->user->name }}</h3>
                <p class="text-sm text-slate-700 font-bold mb-4 flex items-center gap-1.5 opacity-80">
                    <svg class="w-4 h-4 text-slate-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                    {{ $project->village }}
                </p>
                <div class="bg-white/40 rounded-xl p-3 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-900">Deed Document</p>
                            <p class="text-[9px] text-slate-500 font-semibold uppercase tracking-wider">Uploaded by client</p>
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ Storage::url($project->deed_path) }}" target="_blank" class="btn-teal w-full text-center py-3 rounded-xl font-black text-xs uppercase tracking-widest flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                View / Download
            </a>
        </div>
    @empty
        <div class="col-span-full py-16 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/30 text-white mb-4">
                <svg class="w-10 h-10 opacity-60" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
            </div>
            <h3 class="text-xl font-black text-slate-800">No Deeds Found</h3>
            <p class="text-sm font-bold text-slate-500 mt-1 max-w-md mx-auto">There are no uploaded deeds in the system matching your search criteria.</p>
        </div>
    @endforelse
</div>

<div class="mt-8">
    {{ $surveysWithDeeds->links('pagination::tailwind') }}
</div>

@endsection
