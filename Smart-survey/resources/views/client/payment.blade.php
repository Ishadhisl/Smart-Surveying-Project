@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Back --}}
    <div class="mb-8 flex items-center gap-4">
        <a href="{{ route('survey.show', $survey) }}" class="w-10 h-10 glass rounded-xl flex items-center justify-center text-white hover:bg-white/30 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tighter">Make Payment</h1>
            <p class="text-white font-bold opacity-60 text-[10px] uppercase tracking-widest mt-0.5">
                Project #{{ $survey->id }} — {{ $survey->village }}
            </p>
        </div>
    </div>

    {{-- Payment Summary Card --}}
    <div class="glass rounded-[2rem] p-8 mb-6 shadow-xl">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">
                    {{ $paymentType === 'advance' ? 'Advance Payment (1/3 of Estimate)' : 'Final Balance Payment' }}
                </p>
                <div class="text-5xl font-black text-deepteal">Rs. {{ number_format($amountToPay) }}</div>
                <p class="text-xs text-slate-600 font-semibold mt-2">
                    @if($paymentType === 'advance')
                        After payment, your project will be confirmed and the surveyor will schedule a site visit.
                    @else
                        Final payment to complete your survey project. The actual price set by surveyor: Rs. {{ number_format($survey->actual_price) }}
                    @endif
                </p>
            </div>
            <div class="flex flex-col items-center gap-2 text-center">
                <div class="w-16 h-16 bg-deepteal/10 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-deepteal" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Secure Payment</p>
            </div>
        </div>

        {{-- Breakdown --}}
        <div class="mt-6 pt-6 border-t border-white/30 grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white/30 rounded-2xl p-4">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Estimate Price</p>
                <p class="font-black text-slate-900">Rs. {{ number_format($survey->estimate_price) }}</p>
            </div>
            @if($survey->actual_price)
            <div class="bg-white/30 rounded-2xl p-4">
                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1">Final Price</p>
                <p class="font-black text-deepteal">Rs. {{ number_format($survey->actual_price) }}</p>
            </div>
            @endif
            <div class="bg-deepteal/10 rounded-2xl p-4 border border-deepteal/20">
                <p class="text-[9px] font-black text-deepteal uppercase tracking-widest mb-1">Amount Due Now</p>
                <p class="font-black text-deepteal text-lg">Rs. {{ number_format($amountToPay) }}</p>
            </div>
        </div>
    </div>

    {{-- If slip already uploaded --}}
    @if($survey->payment_slip_path && $survey->payment_slip_type === $paymentType)
    <div class="glass rounded-[2rem] p-6 mb-6 shadow-xl border border-emerald-500/30 bg-emerald-50/10">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="font-black text-emerald-700 text-sm">Payment Slip Uploaded</p>
                <p class="text-xs text-emerald-600 font-semibold">
                    Uploaded {{ $survey->payment_slip_uploaded_at?->diffForHumans() }} — Awaiting surveyor verification.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Payment Method Tabs --}}
    <div class="glass rounded-[2rem] shadow-xl overflow-hidden">

        {{-- Tab Buttons --}}
        <div class="flex border-b border-white/20">
            <button type="button" id="tab-payhere" onclick="switchTab('payhere')"
                class="flex-1 py-4 px-6 text-sm font-black uppercase tracking-widest transition-all border-b-2 border-deepteal text-deepteal bg-white/10">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Pay Online (PayHere)
                </span>
            </button>
            <button type="button" id="tab-bank" onclick="switchTab('bank')"
                class="flex-1 py-4 px-6 text-sm font-black uppercase tracking-widest transition-all border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    Bank Transfer
                </span>
            </button>
        </div>

        {{-- ===== TAB 1: PayHere ===== --}}
        <div id="panel-payhere" class="p-8">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 bg-white/30 px-4 py-2 rounded-full mb-4">
                    <svg class="w-4 h-4 text-deepteal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span class="text-xs font-black text-slate-700 uppercase tracking-widest">256-bit SSL Secured</span>
                </div>
                <h3 class="text-xl font-extrabold text-slate-900 mb-1">Pay with PayHere</h3>
                <p class="text-sm text-slate-600 font-semibold">Visa, Mastercard, American Express, eZ Cash, mCash & more</p>
            </div>

            {{-- PayHere Accepted Cards --}}
            <div class="flex justify-center items-center gap-3 mb-8 flex-wrap">
                <div class="bg-white rounded-xl px-3 py-2 shadow-sm text-[11px] font-black text-blue-800">VISA</div>
                <div class="bg-white rounded-xl px-3 py-2 shadow-sm text-[11px] font-black text-red-700">MASTERCARD</div>
                <div class="bg-white rounded-xl px-3 py-2 shadow-sm text-[11px] font-black text-blue-600">AMEX</div>
                <div class="bg-white rounded-xl px-3 py-2 shadow-sm text-[11px] font-black text-green-700">eZ CASH</div>
                <div class="bg-white rounded-xl px-3 py-2 shadow-sm text-[11px] font-black text-orange-600">mCASH</div>
            </div>

            {{-- PayHere Form --}}
            <form method="POST" action="{{ $payHereUrl }}" id="payhere-form">
                {{-- Merchant Info --}}
                <input type="hidden" name="merchant_id"   value="{{ $cfg['merchant_id'] }}">
                <input type="hidden" name="return_url"    value="{{ route('payment.return') }}">
                <input type="hidden" name="cancel_url"    value="{{ route('payment.page', $survey) }}">
                <input type="hidden" name="notify_url"    value="{{ route('payment.notify') }}">

                {{-- Order Info --}}
                <input type="hidden" name="order_id"      value="{{ $orderId }}">
                <input type="hidden" name="items"         value="Smart Survey - {{ ucfirst($paymentType) }} Payment #{{ $survey->id }}">
                <input type="hidden" name="currency"      value="LKR">
                <input type="hidden" name="amount"        value="{{ number_format($amountToPay, 2, '.', '') }}">
                <input type="hidden" name="hash"          value="{{ $hash }}">

                {{-- Customer Info --}}
                <input type="hidden" name="first_name"    value="{{ explode(' ', auth()->user()->name)[0] }}">
                <input type="hidden" name="last_name"     value="{{ implode(' ', array_slice(explode(' ', auth()->user()->name), 1)) ?: 'N/A' }}">
                <input type="hidden" name="email"         value="{{ auth()->user()->email }}">
                <input type="hidden" name="phone"         value="{{ auth()->user()->whatsapp_number }}">
                <input type="hidden" name="address"       value="{{ $survey->village }}">
                <input type="hidden" name="city"          value="Sri Lanka">
                <input type="hidden" name="country"       value="Sri Lanka">

                <button type="submit"
                    class="w-full btn-teal py-5 rounded-2xl text-white font-black text-base uppercase tracking-widest shadow-2xl shadow-deepteal/30 flex items-center justify-center gap-3 hover:scale-[1.01] transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Pay Rs. {{ number_format($amountToPay) }} Now
                </button>
            </form>

            @if($cfg['sandbox'])
            <div class="mt-4 bg-amber-50/50 border border-amber-200/50 rounded-2xl p-4 text-center">
                <p class="text-xs font-bold text-amber-700">⚠️ Sandbox Mode — Test payments only. Use card: 4916217501611292 | Exp: 12/25 | CVV: 100</p>
            </div>
            @endif
        </div>

        {{-- ===== TAB 2: Bank Transfer ===== --}}
        <div id="panel-bank" class="p-8 hidden">
            <div class="mb-6">
                <h3 class="text-xl font-extrabold text-slate-900 mb-1">Bank Transfer Details</h3>
                <p class="text-sm text-slate-600 font-semibold">Transfer the exact amount and upload your receipt below.</p>
            </div>

            {{-- Bank Details Card --}}
            <div class="bg-slate-900/80 rounded-2xl p-6 mb-6 relative overflow-hidden">
                <div class="absolute -top-4 -right-4 w-20 h-20 bg-deepteal/20 rounded-full blur-xl"></div>
                <div class="relative z-10 space-y-4">
                    <div class="flex justify-between items-center border-b border-white/10 pb-3">
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Bank</span>
                        <span class="text-white font-extrabold">{{ config('payhere.bank_name') }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-white/10 pb-3">
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Account Name</span>
                        <span class="text-white font-extrabold">{{ config('payhere.bank_account_name') }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-white/10 pb-3">
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Account No.</span>
                        <span class="text-white font-extrabold font-mono tracking-widest">{{ config('payhere.bank_account_number') }}</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-white/10 pb-3">
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Branch</span>
                        <span class="text-white font-extrabold">{{ config('payhere.bank_branch') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-white/50 text-xs font-bold uppercase tracking-widest">Amount</span>
                        <span class="text-deepteal font-extrabold text-lg">Rs. {{ number_format($amountToPay) }}</span>
                    </div>
                </div>
            </div>

            {{-- Reference Note --}}
            <div class="bg-deepteal/10 border border-deepteal/20 rounded-2xl p-4 mb-6">
                <p class="text-xs font-black text-deepteal uppercase tracking-widest mb-1">Transfer Reference Note</p>
                <p class="font-extrabold text-slate-900 font-mono">SS-{{ $survey->id }}-{{ strtoupper($paymentType) }}</p>
                <p class="text-xs text-slate-500 font-semibold mt-1">Please use this as your payment reference/description.</p>
            </div>

            {{-- Upload Slip --}}
            <form action="{{ route('payment.slip', $survey) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="payment_type" value="{{ $paymentType }}">

                <div class="mb-4">
                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-2">
                        Upload Payment Receipt / Screenshot
                    </label>
                    <label for="payment_slip"
                        class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-white/40 rounded-2xl cursor-pointer bg-white/10 hover:bg-white/20 transition-all group">
                        <svg class="w-8 h-8 text-slate-400 group-hover:text-deepteal transition-colors mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-bold text-slate-500 group-hover:text-slate-700">Click to upload</span>
                        <span class="text-[10px] text-slate-400 font-semibold mt-1">JPG, PNG, PDF — Max 5MB</span>
                        <input id="payment_slip" type="file" name="payment_slip" class="hidden" accept=".jpg,.jpeg,.png,.pdf" required>
                    </label>
                    <div id="file-name" class="text-xs text-deepteal font-bold mt-2 hidden"></div>
                    @error('payment_slip') <p class="text-red-500 text-xs mt-1 font-bold">{{ $message }}</p> @enderror
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 py-4 rounded-2xl text-white font-black text-sm uppercase tracking-widest shadow-xl flex items-center justify-center gap-2 hover:bg-slate-800 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Upload Payment Slip
                </button>
            </form>
        </div>
    </div>

    {{-- Security Note --}}
    <div class="mt-6 text-center">
        <p class="text-white/30 text-[10px] font-black uppercase tracking-widest">
            🔒 Payments are secure and encrypted. For support: {{ auth()->user()->whatsapp_number ?? 'contact us' }}
        </p>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        const tabs = ['payhere', 'bank'];
        tabs.forEach(t => {
            const btn   = document.getElementById('tab-' + t);
            const panel = document.getElementById('panel-' + t);
            if (t === tab) {
                btn.classList.add('border-deepteal', 'text-deepteal', 'bg-white/10');
                btn.classList.remove('border-transparent', 'text-slate-500');
                panel.classList.remove('hidden');
            } else {
                btn.classList.remove('border-deepteal', 'text-deepteal', 'bg-white/10');
                btn.classList.add('border-transparent', 'text-slate-500');
                panel.classList.add('hidden');
            }
        });
    }

    // Show selected filename
    document.getElementById('payment_slip')?.addEventListener('change', function() {
        const nameEl = document.getElementById('file-name');
        if (this.files.length > 0) {
            nameEl.textContent = '✓ ' + this.files[0].name;
            nameEl.classList.remove('hidden');
        }
    });
</script>
@endpush
