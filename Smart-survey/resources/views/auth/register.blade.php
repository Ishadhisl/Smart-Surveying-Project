@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto py-10">
    <div class="glass p-10 rounded-[2.5rem] shadow-xl relative overflow-hidden bg-white/60 border-white">
        <!-- Decoration -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-deepteal/5 rounded-full blur-3xl"></div>
        
        <div class="text-center mb-10 relative z-10">
            <h2 class="text-4xl font-extrabold text-slate-900 mb-2 leading-tight">
                {{ $role === 'admin' ? __('messages.register_as_surveyor') : __('messages.register_as_client') }}
            </h2>
            <p class="text-slate-500 text-sm font-medium">Join our professional surveying network</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-6 relative z-10">
            @csrf
            <input type="hidden" name="role" value="{{ $role }}">

            @if($errors->any() && !$errors->has('name') && !$errors->has('email') && !$errors->has('whatsapp_number') && !$errors->has('license_number') && !$errors->has('password'))
                <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-4 mb-6">
                    <ul class="list-disc list-inside text-red-500 text-xs font-bold">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Full Name</label>
                    <input type="text" name="name" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" placeholder="John Doe" required value="{{ old('name') }}">
                    @error('name') <p class="text-red-500 text-[10px] mt-2 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                    <input type="email" name="email" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" placeholder="example@mail.com" required value="{{ old('email') }}">
                    @error('email') <p class="text-red-500 text-[10px] mt-2 font-bold">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" placeholder="+94 7X XXX XXXX" required value="{{ old('whatsapp_number') }}">
                    @error('whatsapp_number') <p class="text-red-500 text-[10px] mt-2 font-bold">{{ $message }}</p> @enderror
                </div>

                @if($role === 'admin')
                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">{{ __('messages.license_number') }}</label>
                    <input type="text" name="license_number" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" placeholder="123456" required value="{{ old('license_number') }}">
                    @error('license_number') <p class="text-red-500 text-[10px] mt-2 font-bold">{{ $message }}</p> @enderror
                </div>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">{{ __('messages.password') }}</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" required>
                        <button type="button" onclick="togglePassword('password')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition">
                             <svg id="password-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    @error('password') <p class="text-red-500 text-[10px] mt-2 font-bold">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Confirm Password</label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-4 text-slate-800 focus:border-deepteal outline-none shadow-sm" required>
                        <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-deepteal transition">
                             <svg id="password_confirmation-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full btn-teal py-5 rounded-2xl text-white font-bold text-lg shadow-xl tracking-wide transition-all uppercase">
                {{ __('messages.register') }}
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center relative z-10">
            <span class="text-slate-400 text-xs font-medium">Already have an account?</span>
            <a href="{{ route('login') }}" class="ml-2 font-black text-deepteal hover:text-slate-900 transition uppercase text-xs tracking-widest">{{ __('messages.login') }}</a>
        </div>
    </div>
</div>
@endsection
