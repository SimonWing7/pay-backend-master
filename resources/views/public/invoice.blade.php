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

                {{-- Live bank availability pills --}}
                <div class="mb-5">
                    <p class="text-xs font-semibold text-center uppercase tracking-wide mb-3" style="color:#9ca3af;">Live banks</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f3eeff;border:1px solid #ddd5f7;color:#6d28d9;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#7B2FBE;flex-shrink:0;"></span>Wio Bank
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#CF2030;flex-shrink:0;"></span>Mashreq
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>FAB
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#1B4F9C;flex-shrink:0;"></span>CBD
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#006A4E;flex-shrink:0;"></span>ADIB
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('public.invoice.pay', $invoice->uuid) }}" id="paymentForm">
                    @csrf

                    {{-- Customer details fields --}}
                    <div class="mb-5 space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color:#9ca3af;">Your Details</p>

                        @if($errors->any())
                        <div class="p-3 rounded-xl text-xs" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle mr-1"></i>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                Full Name <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                                placeholder="e.g. Sarah Al Mansouri"
                                style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                        </div>

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                Email Address <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}" required
                                placeholder="you@example.com"
                                style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                        </div>

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                Mobile Number <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="tel" name="customer_mobile" value="{{ old('customer_mobile') }}" required
                                placeholder="+971 50 000 0000"
                                style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                        </div>

                        @if($invoice->custom_fields && count($invoice->custom_fields) > 0)
                            @foreach($invoice->custom_fields as $field)
                            @php $fieldKey = 'custom_field_' . \Illuminate\Support\Str::slug($field['label'], '_'); @endphp
                            <div>
                                <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                    {{ $field['label'] }}
                                    @if(!empty($field['required']))
                                        <span style="color:#ef4444;">*</span>
                                    @else
                                        <span style="color:#9ca3af;font-weight:400;"> (optional)</span>
                                    @endif
                                </label>
                                <input type="text"
                                    name="custom_fields[{{ $field['label'] }}]"
                                    value="{{ old('custom_fields.' . $field['label']) }}"
                                    {{ !empty($field['required']) ? 'required' : '' }}
                                    placeholder="{{ $field['label'] }}"
                                    style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                            </div>
                            @endforeach
                        @endif
                    </div>

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

                {{-- "My bank is not listed" escape hatch --}}
                @php
                    $merchant        = $invoice->merchant;
                    $fallbackType    = $merchant->fallback_type ?? null;
                    $hasCardFallback = $fallbackType === 'payment_gateway' && !empty($merchant->fallback_payment_url);
                    $toggleLabel     = $hasCardFallback
                        ? "Can't find your bank? Pay by card instead"
                        : "My bank isn't listed yet";
                    $toggleIcon      = $hasCardFallback ? 'fa-arrow-right' : 'fa-chevron-down';
                @endphp
                <div class="text-center mt-4">
                    <button type="button" onclick="toggleFallback()" id="fallbackToggle"
                        style="color:#9ca3af;background:none;border:none;cursor:pointer;padding:0;font-size:12px;font-weight:500;font-family:inherit;">
                        {{ $toggleLabel }}
                        <i class="fas {{ $toggleIcon }} ml-1 text-xs" id="fallbackChevron"></i>
                    </button>
                </div>

                {{-- Fallback panel (hidden by default) --}}
                <div id="fallbackPanel" class="hidden mt-3 rounded-xl border border-gray-100 overflow-hidden" style="background:#fafbff;">
                    <div class="px-4 py-3 border-b border-gray-100" style="background:#f3f4f6;">
                        <p class="text-xs font-semibold uppercase tracking-wide" style="color:#6b7280;">
                            <i class="fas fa-info-circle mr-1"></i>Alternative Payment
                        </p>
                    </div>
                    <div class="px-4 py-4">
                        @if($fallbackType === 'payment_gateway' && !empty($merchant->fallback_payment_url))
                            <p class="text-sm mb-4" style="color:#6b7280;">
                                You can pay by card via a secure payment page.
                            </p>
                            <a href="{{ $merchant->fallback_payment_url }}" target="_blank" rel="noopener"
                                style="display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px 16px;border-radius:10px;font-weight:700;font-size:14px;background:linear-gradient(135deg,#3d01bd,#00bdff);color:white;text-decoration:none;">
                                <i class="fas fa-credit-card"></i>
                                Pay by Card
                                <i class="fas fa-external-link-alt text-xs" style="opacity:0.7;margin-left:2px;"></i>
                            </a>

                        @elseif($fallbackType === 'bank_transfer')
                            <p class="text-sm mb-4" style="color:#6b7280;">
                                Transfer the exact amount to the account below. Please include the reference shown.
                            </p>
                            <div class="space-y-2.5">
                                <div class="flex justify-between items-center gap-3">
                                    <span class="text-xs font-medium" style="color:#9ca3af;">Amount</span>
                                    <span class="text-sm font-bold" style="color:#3d01bd;">AED {{ number_format($invoice->total_fee, 2) }}</span>
                                </div>
                                @if($merchant->fallback_bank_name)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">Bank</span>
                                    <span class="text-sm font-semibold text-right" style="color:#1f2937;">{{ $merchant->fallback_bank_name }}</span>
                                </div>
                                @endif
                                @if($merchant->fallback_account_name)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">Account Name</span>
                                    <span class="text-sm font-semibold text-right" style="color:#1f2937;">{{ $merchant->fallback_account_name }}</span>
                                </div>
                                @endif
                                @if($merchant->iban)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-xs font-medium flex-shrink-0" style="color:#9ca3af;">IBAN</span>
                                    <span class="text-xs font-mono font-semibold text-right" style="color:#1f2937;">{{ $merchant->iban }}</span>
                                </div>
                                @endif
                                @if($merchant->fallback_reference_note)
                                <div class="pt-3 mt-1 border-t border-gray-100">
                                    <p class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:#9ca3af;">Payment Reference</p>
                                    <p class="text-sm" style="color:#6b7280;">{{ $merchant->fallback_reference_note }}</p>
                                </div>
                                @endif
                            </div>

                        @else
                            <p class="text-sm mb-3" style="color:#6b7280;">
                                Please contact {{ $merchant->merchant_trading_name ?? $merchant->name }} to arrange payment.
                            </p>
                            <div class="space-y-2">
                                @if($merchant->support_email)
                                <a href="mailto:{{ $merchant->support_email }}"
                                    class="flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;text-decoration:none;">
                                    <i class="fas fa-envelope w-4" style="color:#d1d5db;"></i>
                                    {{ $merchant->support_email }}
                                </a>
                                @endif
                                @if($merchant->support_phone)
                                <a href="tel:{{ $merchant->support_phone }}"
                                    class="flex items-center gap-3 text-sm font-medium" style="color:#3d01bd;text-decoration:none;">
                                    <i class="fas fa-phone w-4" style="color:#d1d5db;"></i>
                                    {{ $merchant->support_phone }}
                                </a>
                                @endif
                                @if(!$merchant->support_email && !$merchant->support_phone)
                                <p class="text-sm" style="color:#9ca3af;">Contact the merchant directly for alternative payment options.</p>
                                @endif
                            </div>
                        @endif
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
                    @if($invoice->return_url)
                    @php
                        $returnUrl = $invoice->return_url . (str_contains($invoice->return_url, '?') ? '&' : '?') . 'status=paid&payment_link_id=' . $invoice->uuid;
                    @endphp
                    <a href="{{ $returnUrl }}" class="pay-btn mt-4" style="display:inline-flex;width:auto;padding:12px 24px;">
                        <i class="fas fa-arrow-left"></i> Return to {{ $invoice->merchant->merchant_trading_name ?? $invoice->merchant->name }}
                    </a>
                    @endif
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

        function toggleFallback() {
            var panel = document.getElementById('fallbackPanel');
            var chevron = document.getElementById('fallbackChevron');
            var toggle = document.getElementById('fallbackToggle');
            var isHidden = panel.classList.contains('hidden');
            panel.classList.toggle('hidden');
            if (isHidden) {
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
                toggle.style.color = '#3d01bd';
            } else {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
                toggle.style.color = '#9ca3af';
            }
        }
    </script>
</body>
</html>
