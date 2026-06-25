<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay — {{ $invoice->merchant->merchant_trading_name ?? $invoice->merchant->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        :root { --purple: #3d01bd; --cyan: #00bdff; --navy: #000026; }
        body { background: #f5f6fa; }
        .pay-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #eef0f5;
            box-shadow: 0 4px 24px rgba(0,0,38,0.08);
        }
        .gradient-bar { background: linear-gradient(90deg, var(--purple), var(--cyan)); }
        .gradient-text {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .pay-btn {
            background: linear-gradient(135deg, var(--purple), var(--cyan));
            color: white; font-weight: 700; border-radius: 10px; border: none;
            cursor: pointer; transition: opacity 0.15s; width: 100%;
            padding: 16px 24px; font-size: 16px; display: flex;
            align-items: center; justify-content: center; gap: 10px;
        }
        .pay-btn:hover { opacity: 0.9; }
        .pay-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .status-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f0f1f5; }
        .status-row:last-child { border-bottom: none; }
        .badge-success { background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .badge-warning { background: #fffbeb; color: #d97706; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .badge-danger  { background: #fef2f2; color: #dc2626; font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center py-10 px-4">

        {{-- Logo / Brand header --}}
        <div class="mb-6 text-center">
            <div class="gradient-bar h-1 w-16 rounded-full mx-auto mb-4"></div>
            <p class="text-xs font-semibold tracking-widest uppercase" style="color:#9ca3af;">Powered by Edfundo Pay</p>
        </div>

        <div class="pay-card w-full max-w-md overflow-hidden">

            {{-- Merchant header --}}
            <div class="px-6 pt-8 pb-6 text-center border-b border-gray-100">
                <div class="mx-auto mb-4" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#3d01bd,#00bdff);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-store text-white text-xl"></i>
                </div>
                <h1 class="text-xl font-bold" style="color:#000026;">
                    {{ $invoice->merchant->merchant_trading_name ?? $invoice->merchant->name }}
                </h1>
                <p class="text-sm text-gray-400 mt-1">Payment Link</p>
            </div>

            {{-- Flash messages --}}
            @if(session('error'))
            <div class="mx-6 mt-5 p-4 rounded-xl text-sm flex items-center gap-3" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                {{ session('error') }}
            </div>
            @endif
            @if(session('success'))
            <div class="mx-6 mt-5 p-4 rounded-xl text-sm flex items-center gap-3" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
                <i class="fas fa-check-circle text-green-400"></i>
                {{ session('success') }}
            </div>
            @endif

            {{-- Invoice details --}}
            <div class="px-6 py-5">
                <div class="status-row">
                    <span class="text-sm text-gray-500">Reference</span>
                    <span class="text-xs font-mono text-gray-600">{{ substr($invoice->uuid, 0, 16) }}…</span>
                </div>
                @if($invoice->consumer)
                <div class="status-row">
                    <span class="text-sm text-gray-500">For</span>
                    <span class="text-sm font-medium text-gray-700">{{ $invoice->consumer->name }}</span>
                </div>
                @endif
                <div class="status-row">
                    <span class="text-sm text-gray-500">Status</span>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">Paid</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">Failed</span>
                    @else
                        <span class="badge-warning">{{ $invoice->status->label() }}</span>
                    @endif
                </div>

                {{-- Line items --}}
                @if($invoice->invoiceDetails->count() > 0)
                <div class="mt-4 mb-2">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-3">Items</p>
                    @foreach($invoice->invoiceDetails as $detail)
                    <div class="flex justify-between items-start py-2.5 border-b border-gray-50">
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ $detail->title }}</p>
                            @if($detail->product)
                            <p class="text-xs text-gray-400">{{ $detail->product->name }}</p>
                            @endif
                        </div>
                        <p class="text-sm font-semibold text-gray-800 flex-shrink-0 ml-4">AED {{ number_format($detail->fee, 2) }}</p>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Total --}}
                <div class="flex justify-between items-center pt-4 mt-2">
                    <span class="text-base font-bold text-gray-800">Total</span>
                    <span class="text-2xl font-extrabold gradient-text">AED {{ number_format($invoice->total_fee, 2) }}</span>
                </div>
            </div>

            {{-- CTA --}}
            <div class="px-6 pb-8">
                @if($invoice->status->value === 0)
                <form method="POST" action="{{ route('public.invoice.pay', $invoice->uuid) }}" id="paymentForm">
                    @csrf
                    <button type="submit" id="payButton" class="pay-btn">
                        <i class="fas fa-university"></i>
                        Pay AED {{ number_format($invoice->total_fee, 2) }} by Bank
                    </button>
                </form>
                <div id="loadingState" class="hidden mt-4 text-center">
                    <div class="inline-flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Connecting to your bank…
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-center gap-5 text-xs text-gray-400">
                    <span><i class="fas fa-lock mr-1"></i>256-bit encrypted</span>
                    <span><i class="fas fa-shield-alt mr-1"></i>UAE Central Bank regulated</span>
                </div>

                @elseif($invoice->status->value === 10)
                <div class="text-center py-6">
                    <div class="mx-auto mb-3" style="width:52px;height:52px;border-radius:50%;background:#ecfdf5;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    </div>
                    <p class="font-bold text-gray-800">Payment Complete</p>
                    <p class="text-sm text-gray-400 mt-1">This invoice has already been paid. Thank you!</p>
                </div>

                @elseif($invoice->status->value === 20)
                <div class="text-center py-6">
                    <div class="mx-auto mb-3" style="width:52px;height:52px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-times-circle text-red-500 text-xl"></i>
                    </div>
                    <p class="font-bold text-gray-800">Payment Failed</p>
                    <p class="text-sm text-gray-400 mt-1">Please contact {{ $invoice->merchant->merchant_trading_name ?? $invoice->merchant->name }} for assistance.</p>
                </div>
                @endif
            </div>

        </div>

        <p class="mt-6 text-xs text-gray-400">
            &copy; {{ date('Y') }} Edfundo Pay. All rights reserved.
        </p>
    </div>

    <script>
        const form = document.getElementById('paymentForm');
        const payButton = document.getElementById('payButton');
        const loadingState = document.getElementById('loadingState');
        if (form) {
            form.addEventListener('submit', function() {
                payButton.disabled = true;
                loadingState.classList.remove('hidden');
            });
        }
    </script>
</body>
</html>
