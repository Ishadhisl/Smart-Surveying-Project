@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="glass p-8 rounded-3xl shadow-2xl border border-white/30">
        <div class="flex items-center gap-4 mb-8">
            <div class="p-3 bg-deepteal rounded-2xl shadow-lg">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tighter leading-none">Your Feedback</h1>
                <p class="text-[10px] uppercase tracking-[0.2em] font-bold text-white/60 mt-1">Help us improve our service</p>
            </div>
        </div>

        <form action="{{ route('feedback.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Star Rating -->
            <div>
                <label class="block text-sm font-bold text-slate-800 uppercase tracking-widest mb-3">Rate our service</label>
                <div class="flex flex-row-reverse justify-end gap-2">
                    @for($i = 5; $i >= 1; $i--)
                        <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}" class="hidden peer" required>
                        <label for="star{{ $i }}" class="text-3xl text-white/30 cursor-pointer hover:text-yellow-400 peer-checked:text-yellow-400 transition-all duration-200">
                            ★
                        </label>
                    @endfor
                </div>
                @error('rating')
                    <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <!-- Comment -->
            <div>
                <label for="comment" class="block text-sm font-bold text-slate-800 uppercase tracking-widest mb-3">Your Experience</label>
                <textarea 
                    name="comment" 
                    id="comment" 
                    rows="5" 
                    class="w-full bg-white/40 border-2 border-white/50 rounded-2xl p-4 text-slate-900 placeholder-slate-500 focus:outline-none focus:border-deepteal transition-all"
                    placeholder="Tell us what you think about our service..."
                >{{ old('comment') }}</textarea>
                @error('comment')
                    <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full btn-teal py-4 rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl flex items-center justify-center gap-3">
                Submit Feedback
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>
    </div>

    <!-- Info Card -->
    <div class="mt-8 bg-white/20 p-6 rounded-2xl border border-white/10 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-white/30 flex items-center justify-center text-slate-800 font-bold">i</div>
        <p class="text-xs text-slate-800 font-medium leading-relaxed">
            Your feedback helps us maintain our <b>Precision First</b> standard. We review every submission to ensure the highest quality of land surveying services in Sri Lanka.
        </p>
    </div>
</div>

<style>
    /* Fancy star hover effect */
    label:hover ~ label {
        color: #fbbf24 !important;
    }
</style>
@endsection
