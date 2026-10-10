@extends('layouts.admin')

@section('admin-content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tighter leading-none">Client Feedback</h1>
            <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-white/60 mt-1">Review service ratings and comments</p>
        </div>
    </div>

    <!-- Feedback Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-glass p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Submissions</p>
            <p class="text-3xl font-black text-slate-900">{{ $feedbacks->total() }}</p>
        </div>
        <div class="stat-glass p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Average Rating</p>
            <p class="text-3xl font-black text-deepteal">
                {{ number_format($feedbacks->avg('rating'), 1) }} <span class="text-lg">/ 5.0</span>
            </p>
        </div>
        <div class="stat-glass p-6">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Feedback Rate</p>
            <p class="text-3xl font-black text-slate-900">100%</p>
        </div>
    </div>

    <!-- Feedback Table -->
    <div class="glass rounded-[2rem] overflow-hidden shadow-2xl">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($feedbacks as $feedback)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-deepteal/10 flex items-center justify-center text-deepteal font-bold text-xs uppercase">
                                {{ substr($feedback->user->name, 0, 2) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-900 text-xs">{{ $feedback->user->name }}</p>
                                <p class="text-[9px] text-slate-500 font-bold uppercase tracking-tighter">{{ $feedback->user->role }}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="flex text-yellow-500">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $feedback->rating)
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @else
                                    <svg class="w-3.5 h-3.5 text-white/30" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                @endif
                            @endfor
                        </div>
                    </td>
                    <td>
                        <p class="text-xs text-slate-700 font-medium max-w-md italic">
                            "{{ $feedback->comment ?? 'No comment provided' }}"
                        </p>
                    </td>
                    <td>
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-tighter">
                            {{ $feedback->created_at->format('M d, Y') }}
                        </p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">
                            {{ $feedback->created_at->diffForHumans() }}
                        </p>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center py-20">
                        <div class="flex flex-col items-center">
                            <div class="w-16 h-16 bg-white/10 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            </div>
                            <p class="text-sm font-bold text-slate-800 uppercase tracking-widest">No feedback received yet</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($feedbacks->hasPages())
        <div class="p-6 border-t border-white/10">
            {{ $feedbacks->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
