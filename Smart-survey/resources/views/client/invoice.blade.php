<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Project #{{ $survey->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .print-area { max-width: 800px; margin: 40px auto; background: white; padding: 60px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border-radius: 8px; }
        
        @media print {
            body { background: white; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-area { margin: 0; padding: 20px; box-shadow: none; max-width: 100%; border-radius: 0; }
            .no-print { display: none !important; }
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(6, 95, 70, 0.05); /* very light emerald */
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="text-slate-800 relative">

    <div class="fixed top-6 right-6 no-print flex gap-4">
        <a href="{{ route('survey.show', $survey) }}" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-800 font-bold rounded-lg transition-colors">
            Back to Project
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-[#008080] hover:bg-[#006666] text-white font-bold rounded-lg shadow-lg flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print / Save as PDF
        </button>
    </div>

    <div class="print-area relative overflow-hidden">
        
        <div class="watermark">FULLY PAID</div>

        <!-- Header -->
        <div class="flex justify-between items-start border-b-2 border-slate-100 pb-8 mb-8 relative z-10">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="p-1.5 bg-[#008080] rounded">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <div class="leading-none">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tighter">Smart Survey</h1>
                        <span class="text-[9px] font-black tracking-widest text-[#008080] uppercase block mt-0.5">Precision First</span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 font-semibold mt-4">123 Surveyors Lane,<br>Colombo 00700, Sri Lanka<br>+94 77 123 4567<br>contact@smartsurvey.lk</p>
            </div>
            <div class="text-right">
                <h2 class="text-4xl font-extrabold text-slate-900 mb-2">INVOICE</h2>
                <p class="text-sm font-bold text-slate-500 mb-1">Invoice #: <span class="text-slate-800">INV-{{ date('Y') }}-{{ str_pad($survey->id, 4, '0', STR_PAD_LEFT) }}</span></p>
                <p class="text-sm font-bold text-slate-500 mb-1">Date: <span class="text-slate-800">{{ now()->format('M d, Y') }}</span></p>
                <p class="text-sm font-bold text-slate-500">Project ID: <span class="text-slate-800">#{{ $survey->id }}</span></p>
            </div>
        </div>

        <!-- Billing Info -->
        <div class="grid grid-cols-2 gap-12 mb-12 relative z-10">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Billed To</p>
                <h3 class="text-lg font-bold text-slate-900">{{ $survey->user->name }}</h3>
                <p class="text-sm font-medium text-slate-600 mt-1">{{ $survey->user->whatsapp_number }}<br>{{ $survey->user->email }}<br>Village: {{ $survey->village }}</p>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-400 mb-3">Payment Status</p>
                <div class="inline-block bg-emerald-100 text-emerald-800 px-4 py-2 rounded-lg border border-emerald-200">
                    <span class="block text-xs font-black uppercase tracking-widest opacity-80 mb-0.5">Status</span>
                    <span class="font-extrabold text-lg flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        FULLY PAID
                    </span>
                </div>
            </div>
        </div>

        <!-- Details -->
        <table class="w-full text-left border-collapse mb-12 relative z-10">
            <thead>
                <tr class="border-b-2 border-slate-800">
                    <th class="py-3 px-4 font-black uppercase tracking-wider text-xs text-slate-700 w-2/3">Description</th>
                    <th class="py-3 px-4 font-black uppercase tracking-wider text-xs text-slate-700 text-right w-1/3">Amount (LKR)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-slate-200">
                    <td class="py-5 px-4">
                        <p class="font-bold text-slate-900">Land Surveying Services</p>
                        <p class="text-sm text-slate-500 mt-1">
                            Property located at {{ $survey->village }} (Size: 
                            @if($survey->size_acres > 0)
                                {{ $survey->size_acres + 0 }} Acres, 
                            @endif
                            {{ $survey->size_perches + 0 }} Perches). 
                            Includes topological mapping, boundary verification, and final deed drafting.
                        </p>
                    </td>
                    <td class="py-5 px-4 text-right font-bold text-slate-800 align-top">
                        Rs. {{ number_format($survey->actual_price ?? $survey->estimated_price, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end relative z-10">
            <div class="w-1/2">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="font-bold text-slate-600">Subtotal</span>
                    <span class="font-bold text-slate-900">Rs. {{ number_format($survey->actual_price ?? $survey->estimated_price, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100 text-emerald-700">
                    <span class="font-bold">Advance Payment Received</span>
                    <span class="font-bold">- Rs. {{ number_format(($survey->actual_price ?? $survey->estimated_price) / 3, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="font-bold text-slate-600">Balance Paid</span>
                    <span class="font-bold text-slate-900">- Rs. {{ number_format($survey->balance_amount, 2) }}</span>
                </div>
                <div class="flex justify-between py-4 mt-2">
                    <span class="text-lg font-black text-slate-900">Total Due</span>
                    <span class="text-xl font-black text-slate-900">Rs. 0.00</span>
                </div>
            </div>
        </div>

        <div class="mt-20 pt-8 border-t-2 border-slate-100 text-center relative z-10">
            <h4 class="font-black text-slate-900 mb-2">Thank you for your business!</h4>
            <p class="text-sm font-semibold text-slate-500">If you have any questions about this invoice, please contact our support team.</p>
        </div>

    </div>
</body>
</html>
