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
            background: linear-gradient(135deg, #000026, #1a0050);
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

        .pay-btn-altareq {
            background: none;
            border: none;
            padding: 0;
            margin: 0 auto;
            display: block;
            cursor: pointer;
            width: 100%;
            max-width: 343px;
            transition: opacity 0.2s ease, transform 0.1s ease;
        }
        .pay-btn-altareq:hover:not(:disabled) { opacity: 0.88; transform: translateY(-1px); }
        .pay-btn-altareq:active:not(:disabled) { transform: translateY(0); }
        .pay-btn-altareq:disabled { opacity: 0.55; cursor: not-allowed; }
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
                @if($invoice->merchant->logo_path)
                    <div class="mx-auto mb-4" style="width:64px;height:64px;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f9fafb;border:1.5px solid #e5e7eb;">
                        <img src="{{ $invoice->merchant->logo_url }}" alt="{{ $invoice->merchant->merchant_trading_name ?? $invoice->merchant->name }}" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                    </div>
                @else
                    <div class="mx-auto mb-4" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#3d01bd,#00bdff);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-store text-white text-xl"></i>
                    </div>
                @endif
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
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-3">Order Details</p>
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
                @if($invoice->status->value === 0 || ($invoice->link_type ?? 'personal') === 'open')

                {{-- Live bank availability pills --}}
                <div class="mb-5">
                    <p class="text-xs font-semibold text-center uppercase tracking-wide mb-3" style="color:#9ca3af;">Live banks</p>
                    <div class="flex flex-wrap justify-center gap-2">
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>Wio Bank
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>Mashreq
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>FAB
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:11px;font-weight:700;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;">
                            <span style="width:7px;height:7px;border-radius:50%;background:#009A44;flex-shrink:0;"></span>CBD
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
                            <input type="text" name="customer_name" value="{{ old('customer_name', request('customer_name')) }}" required
                                placeholder="e.g. Sarah Al Mansouri"
                                style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                        </div>

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                Email Address <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="email" name="customer_email" value="{{ old('customer_email', request('customer_email')) }}" required
                                placeholder="you@example.com"
                                style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                        </div>

                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;">
                                Mobile Number <span style="color:#ef4444;">*</span>
                            </label>
                            <input type="tel" name="customer_mobile" value="{{ old('customer_mobile', request('customer_mobile', '+971')) }}" required
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
                                    value="{{ old('custom_fields.' . $field['label'], request('custom_fields.' . $field['label'])) }}"
                                    {{ !empty($field['required']) ? 'required' : '' }}
                                    placeholder="{{ $field['label'] }}"
                                    style="width:100%;padding:10px 14px;border-radius:10px;border:1.5px solid #e2e5ef;font-size:14px;font-family:inherit;outline:none;transition:border-color 0.15s;box-sizing:border-box;"
                                    onfocus="this.style.borderColor='#3d01bd'" onblur="this.style.borderColor='#e2e5ef'">
                            </div>
                            @endforeach
                        @endif
                    </div>

                    <button type="submit" id="payButton" class="pay-btn-altareq">
                    <svg width="369" height="85" viewBox="0 0 369 85" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:100%;max-width:343px;height:auto;display:block;margin:0 auto;"><g filter="url(#filter0_d_66_326)"><rect x="13" width="343" height="54" rx="27" fill="url(#paint0_linear_66_326)"/><path d="M124 13C115.992 13 109.5 19.2855 109.5 27.0389C109.5 34.7923 115.992 41.0778 124 41.0778C132.008 41.0778 138.5 34.7923 138.5 27.0389C138.5 19.2855 132.008 13 124 13ZM126.691 29.6448H121.309V24.4347H126.691V29.6448Z" fill="white"/><path d="M129.015 31.8941H118.987V22.1854H129.016V31.8941H129.015ZM121.309 29.6448H126.691V24.4347H121.309V29.6448Z" fill="url(#paint1_linear_66_326)"/><path d="M116.657 19.9271V34.1488H131.345V19.9271H116.657ZM129.015 31.8941H118.987V22.1854H129.016V31.8941H129.015Z" fill="url(#paint2_linear_66_326)"/><path d="M114.326 17.6707V36.4053H133.676V17.6707H114.326ZM131.345 34.1506H116.655V19.9289H131.345V34.1506Z" fill="url(#paint3_linear_66_326)"/><path d="M138.5 41.0778L126.691 29.6448H121.309L133.117 41.0778H138.5Z" fill="url(#paint4_radial_66_326)"/><path d="M149.564 32V22.396H154.38C155.089 22.396 155.673 22.522 156.13 22.774C156.597 23.026 156.947 23.3807 157.18 23.838C157.413 24.2953 157.53 24.8413 157.53 25.476C157.53 26.1013 157.409 26.6473 157.166 27.114C156.923 27.5807 156.559 27.9447 156.074 28.206C155.598 28.458 155.001 28.584 154.282 28.584H151.384V32H149.564ZM151.384 27.03H154.226C154.693 27.03 155.052 26.8947 155.304 26.624C155.565 26.344 155.696 25.9613 155.696 25.476C155.696 25.1493 155.64 24.874 155.528 24.65C155.416 24.426 155.253 24.2533 155.038 24.132C154.823 24.0107 154.553 23.95 154.226 23.95H151.384V27.03ZM160.242 32.168C160.037 32.168 159.799 32.14 159.528 32.084C159.267 32.0373 159.01 31.944 158.758 31.804C158.516 31.664 158.315 31.4587 158.156 31.188C157.998 30.908 157.918 30.544 157.918 30.096C157.918 29.592 158.03 29.1767 158.254 28.85C158.478 28.514 158.791 28.2527 159.192 28.066C159.603 27.87 160.088 27.7347 160.648 27.66C161.218 27.576 161.838 27.534 162.51 27.534V26.932C162.51 26.7173 162.478 26.526 162.412 26.358C162.347 26.19 162.226 26.0593 162.048 25.966C161.88 25.8633 161.624 25.812 161.278 25.812C160.933 25.812 160.662 25.854 160.466 25.938C160.27 26.022 160.135 26.1247 160.06 26.246C159.986 26.3673 159.948 26.4933 159.948 26.624V26.82H158.296C158.287 26.7733 158.282 26.7267 158.282 26.68C158.282 26.6333 158.282 26.5773 158.282 26.512C158.282 26.092 158.408 25.728 158.66 25.42C158.912 25.112 159.262 24.8787 159.71 24.72C160.158 24.552 160.676 24.468 161.264 24.468C161.936 24.468 162.487 24.5613 162.916 24.748C163.355 24.9347 163.682 25.2007 163.896 25.546C164.111 25.8913 164.218 26.3113 164.218 26.806V30.278C164.218 30.4553 164.27 30.5813 164.372 30.656C164.475 30.7307 164.587 30.768 164.708 30.768H165.156V31.944C165.063 31.9813 164.928 32.0233 164.75 32.07C164.573 32.126 164.354 32.154 164.092 32.154C163.85 32.154 163.63 32.112 163.434 32.028C163.248 31.9533 163.089 31.8413 162.958 31.692C162.828 31.5427 162.734 31.3653 162.678 31.16H162.594C162.436 31.356 162.244 31.5333 162.02 31.692C161.806 31.8413 161.549 31.958 161.25 32.042C160.961 32.126 160.625 32.168 160.242 32.168ZM160.76 30.768C161.04 30.768 161.288 30.726 161.502 30.642C161.726 30.558 161.908 30.4413 162.048 30.292C162.198 30.1427 162.31 29.9607 162.384 29.746C162.468 29.5313 162.51 29.298 162.51 29.046V28.71C161.997 28.71 161.521 28.7427 161.082 28.808C160.653 28.8733 160.308 28.9947 160.046 29.172C159.794 29.3493 159.668 29.6013 159.668 29.928C159.668 30.096 159.706 30.2453 159.78 30.376C159.864 30.4973 159.986 30.5953 160.144 30.67C160.312 30.7353 160.518 30.768 160.76 30.768ZM166.811 34.548C166.466 34.548 166.186 34.52 165.971 34.464C165.757 34.4173 165.635 34.3893 165.607 34.38V33.162H166.391C166.587 33.162 166.783 33.1107 166.979 33.008C167.175 32.9053 167.353 32.7653 167.511 32.588C167.67 32.4107 167.787 32.2147 167.861 32L164.991 24.636H166.783L168.155 28.164C168.221 28.3227 168.286 28.5233 168.351 28.766C168.426 29.0087 168.501 29.2607 168.575 29.522C168.65 29.774 168.711 30.0027 168.757 30.208H168.827C168.865 30.068 168.907 29.9093 168.953 29.732C169 29.5547 169.047 29.3727 169.093 29.186C169.149 28.9993 169.205 28.8173 169.261 28.64C169.317 28.4627 169.364 28.3087 169.401 28.178L170.521 24.636H172.257L169.793 31.622C169.653 32.014 169.495 32.3873 169.317 32.742C169.149 33.0967 168.949 33.4093 168.715 33.68C168.491 33.9507 168.221 34.1607 167.903 34.31C167.595 34.4687 167.231 34.548 166.811 34.548ZM177.782 32.168C177.073 32.168 176.494 31.9767 176.046 31.594C175.608 31.202 175.388 30.5393 175.388 29.606V24.636H177.096V29.298C177.096 29.5593 177.124 29.7787 177.18 29.956C177.246 30.124 177.334 30.264 177.446 30.376C177.568 30.4787 177.712 30.5533 177.88 30.6C178.048 30.6467 178.235 30.67 178.44 30.67C178.748 30.67 179.024 30.5953 179.266 30.446C179.518 30.2967 179.714 30.0913 179.854 29.83C180.004 29.5593 180.078 29.256 180.078 28.92V24.636H181.786V32H180.372L180.232 31.02H180.134C179.966 31.244 179.766 31.4447 179.532 31.622C179.308 31.79 179.047 31.9207 178.748 32.014C178.459 32.1167 178.137 32.168 177.782 32.168ZM186.176 32.168C185.644 32.168 185.178 32.112 184.776 32C184.375 31.8787 184.039 31.7153 183.768 31.51C183.498 31.3047 183.292 31.0667 183.152 30.796C183.022 30.516 182.956 30.2127 182.956 29.886C182.956 29.8393 182.956 29.7973 182.956 29.76C182.966 29.7227 182.97 29.6947 182.97 29.676H184.65C184.65 29.6947 184.65 29.7133 184.65 29.732C184.65 29.7507 184.65 29.7693 184.65 29.788C184.66 30.04 184.739 30.2453 184.888 30.404C185.038 30.5533 185.234 30.6607 185.476 30.726C185.719 30.7913 185.976 30.824 186.246 30.824C186.489 30.824 186.718 30.8007 186.932 30.754C187.156 30.698 187.338 30.614 187.478 30.502C187.628 30.3807 187.702 30.2267 187.702 30.04C187.702 29.7973 187.609 29.6107 187.422 29.48C187.236 29.3493 186.988 29.242 186.68 29.158C186.382 29.074 186.06 28.9853 185.714 28.892C185.406 28.808 185.098 28.7147 184.79 28.612C184.482 28.5093 184.202 28.3787 183.95 28.22C183.708 28.0613 183.507 27.856 183.348 27.604C183.199 27.352 183.124 27.0393 183.124 26.666C183.124 26.302 183.204 25.9847 183.362 25.714C183.521 25.4433 183.74 25.2193 184.02 25.042C184.3 24.8553 184.632 24.7153 185.014 24.622C185.397 24.5287 185.812 24.482 186.26 24.482C186.699 24.482 187.096 24.5287 187.45 24.622C187.814 24.7153 188.127 24.8507 188.388 25.028C188.659 25.196 188.864 25.406 189.004 25.658C189.154 25.9007 189.228 26.1713 189.228 26.47C189.228 26.5353 189.224 26.6007 189.214 26.666C189.214 26.7313 189.214 26.7687 189.214 26.778H187.548V26.68C187.548 26.5027 187.492 26.3533 187.38 26.232C187.278 26.1013 187.124 25.9987 186.918 25.924C186.722 25.8493 186.475 25.812 186.176 25.812C185.952 25.812 185.756 25.8307 185.588 25.868C185.42 25.9053 185.28 25.9567 185.168 26.022C185.066 26.0873 184.986 26.162 184.93 26.246C184.874 26.33 184.846 26.4233 184.846 26.526C184.846 26.7033 184.916 26.8433 185.056 26.946C185.196 27.0487 185.383 27.1373 185.616 27.212C185.859 27.2867 186.12 27.366 186.4 27.45C186.736 27.5433 187.082 27.6413 187.436 27.744C187.791 27.8373 188.118 27.9587 188.416 28.108C188.724 28.2573 188.972 28.4673 189.158 28.738C189.345 29.0087 189.438 29.368 189.438 29.816C189.438 30.2453 189.354 30.614 189.186 30.922C189.028 31.2207 188.799 31.4633 188.5 31.65C188.211 31.8273 187.866 31.958 187.464 32.042C187.072 32.126 186.643 32.168 186.176 32.168ZM190.581 23.502V21.878H192.289V23.502H190.581ZM190.581 32V24.636H192.289V32H190.581ZM193.808 32V24.636H195.236L195.376 25.616H195.474C195.642 25.392 195.838 25.196 196.062 25.028C196.295 24.8507 196.557 24.7153 196.846 24.622C197.145 24.5193 197.471 24.468 197.826 24.468C198.293 24.468 198.703 24.552 199.058 24.72C199.422 24.888 199.707 25.1587 199.912 25.532C200.117 25.9053 200.22 26.4047 200.22 27.03V32H198.498V27.338C198.498 27.0767 198.465 26.862 198.4 26.694C198.344 26.5167 198.255 26.3767 198.134 26.274C198.022 26.162 197.882 26.0827 197.714 26.036C197.546 25.9893 197.359 25.966 197.154 25.966C196.846 25.966 196.566 26.0407 196.314 26.19C196.071 26.3393 195.875 26.5447 195.726 26.806C195.586 27.0673 195.516 27.3707 195.516 27.716V32H193.808ZM202.902 34.548C202.575 34.548 202.267 34.4827 201.978 34.352C201.688 34.2213 201.455 34.0253 201.278 33.764C201.1 33.512 201.012 33.1993 201.012 32.826C201.012 32.4153 201.128 32.0933 201.362 31.86C201.604 31.6173 201.87 31.4353 202.16 31.314C201.945 31.202 201.768 31.0527 201.628 30.866C201.497 30.6793 201.432 30.4553 201.432 30.194C201.432 29.8393 201.562 29.5593 201.824 29.354C202.085 29.1393 202.384 28.9993 202.72 28.934C202.374 28.7193 202.108 28.444 201.922 28.108C201.744 27.772 201.656 27.3893 201.656 26.96C201.656 26.4467 201.777 26.008 202.02 25.644C202.272 25.2707 202.636 24.9813 203.112 24.776C203.588 24.5707 204.162 24.468 204.834 24.468C205.104 24.468 205.366 24.4867 205.618 24.524C205.87 24.5613 206.098 24.6173 206.304 24.692C206.63 24.496 206.854 24.2767 206.976 24.034C207.097 23.782 207.162 23.5767 207.172 23.418H208.838C208.838 23.7447 208.772 24.034 208.642 24.286C208.52 24.538 208.348 24.748 208.124 24.916C207.9 25.0747 207.629 25.1913 207.312 25.266C207.545 25.4713 207.722 25.7187 207.844 26.008C207.965 26.2973 208.026 26.6147 208.026 26.96C208.026 27.4733 207.904 27.9167 207.662 28.29C207.428 28.6633 207.078 28.9527 206.612 29.158C206.154 29.3633 205.59 29.466 204.918 29.466H203.784C203.588 29.466 203.434 29.508 203.322 29.592C203.219 29.676 203.168 29.7927 203.168 29.942C203.168 30.0633 203.214 30.1707 203.308 30.264C203.41 30.3573 203.55 30.404 203.728 30.404H206.948C207.498 30.404 207.951 30.586 208.306 30.95C208.67 31.3047 208.852 31.762 208.852 32.322C208.852 32.742 208.74 33.12 208.516 33.456C208.301 33.792 207.998 34.058 207.606 34.254C207.214 34.45 206.756 34.548 206.234 34.548H202.902ZM203.518 33.316H206.22C206.397 33.316 206.551 33.2833 206.682 33.218C206.822 33.162 206.929 33.078 207.004 32.966C207.088 32.854 207.13 32.728 207.13 32.588C207.13 32.3453 207.05 32.1633 206.892 32.042C206.742 31.9207 206.556 31.86 206.332 31.86H203.518C203.294 31.86 203.107 31.9253 202.958 32.056C202.808 32.196 202.734 32.3733 202.734 32.588C202.734 32.8027 202.804 32.9753 202.944 33.106C203.093 33.246 203.284 33.316 203.518 33.316ZM204.848 28.262C205.342 28.262 205.711 28.15 205.954 27.926C206.206 27.6927 206.332 27.3707 206.332 26.96C206.332 26.5493 206.206 26.232 205.954 26.008C205.711 25.784 205.342 25.672 204.848 25.672C204.353 25.672 203.98 25.784 203.728 26.008C203.485 26.232 203.364 26.5493 203.364 26.96C203.364 27.2307 203.415 27.464 203.518 27.66C203.63 27.856 203.793 28.0053 204.008 28.108C204.232 28.2107 204.512 28.262 204.848 28.262ZM211.318 32L215.056 22.396H217.352L221.104 32H219.13L218.36 29.956H213.964L213.194 32H211.318ZM214.538 28.416H217.772L216.806 25.812C216.769 25.7187 216.722 25.602 216.666 25.462C216.619 25.3127 216.568 25.154 216.512 24.986C216.456 24.8087 216.4 24.6313 216.344 24.454C216.288 24.2767 216.237 24.118 216.19 23.978H216.12C216.064 24.1647 215.994 24.3793 215.91 24.622C215.835 24.8647 215.761 25.0933 215.686 25.308C215.611 25.5227 215.551 25.6907 215.504 25.812L214.538 28.416ZM221.784 32V21.878H223.492V32H221.784ZM227.517 32V23.964H224.451V22.396H232.417V23.964H229.337V32H227.517ZM234.148 32.168C233.943 32.168 233.705 32.14 233.434 32.084C233.173 32.0373 232.916 31.944 232.664 31.804C232.421 31.664 232.221 31.4587 232.062 31.188C231.903 30.908 231.824 30.544 231.824 30.096C231.824 29.592 231.936 29.1767 232.16 28.85C232.384 28.514 232.697 28.2527 233.098 28.066C233.509 27.87 233.994 27.7347 234.554 27.66C235.123 27.576 235.744 27.534 236.416 27.534V26.932C236.416 26.7173 236.383 26.526 236.318 26.358C236.253 26.19 236.131 26.0593 235.954 25.966C235.786 25.8633 235.529 25.812 235.184 25.812C234.839 25.812 234.568 25.854 234.372 25.938C234.176 26.022 234.041 26.1247 233.966 26.246C233.891 26.3673 233.854 26.4933 233.854 26.624V26.82H232.202C232.193 26.7733 232.188 26.7267 232.188 26.68C232.188 26.6333 232.188 26.5773 232.188 26.512C232.188 26.092 232.314 25.728 232.566 25.42C232.818 25.112 233.168 24.8787 233.616 24.72C234.064 24.552 234.582 24.468 235.17 24.468C235.842 24.468 236.393 24.5613 236.822 24.748C237.261 24.9347 237.587 25.2007 237.802 25.546C238.017 25.8913 238.124 26.3113 238.124 26.806V30.278C238.124 30.4553 238.175 30.5813 238.278 30.656C238.381 30.7307 238.493 30.768 238.614 30.768H239.062V31.944C238.969 31.9813 238.833 32.0233 238.656 32.07C238.479 32.126 238.259 32.154 237.998 32.154C237.755 32.154 237.536 32.112 237.34 32.028C237.153 31.9533 236.995 31.8413 236.864 31.692C236.733 31.5427 236.64 31.3653 236.584 31.16H236.5C236.341 31.356 236.15 31.5333 235.926 31.692C235.711 31.8413 235.455 31.958 235.156 32.042C234.867 32.126 234.531 32.168 234.148 32.168ZM234.666 30.768C234.946 30.768 235.193 30.726 235.408 30.642C235.632 30.558 235.814 30.4413 235.954 30.292C236.103 30.1427 236.215 29.9607 236.29 29.746C236.374 29.5313 236.416 29.298 236.416 29.046V28.71C235.903 28.71 235.427 28.7427 234.988 28.808C234.559 28.8733 234.213 28.9947 233.952 29.172C233.7 29.3493 233.574 29.6013 233.574 29.928C233.574 30.096 233.611 30.2453 233.686 30.376C233.77 30.4973 233.891 30.5953 234.05 30.67C234.218 30.7353 234.423 30.768 234.666 30.768ZM239.737 32V24.636H241.165L241.305 25.798H241.403C241.497 25.5647 241.609 25.35 241.739 25.154C241.879 24.9487 242.057 24.7853 242.271 24.664C242.486 24.5333 242.747 24.468 243.055 24.468C243.205 24.468 243.34 24.482 243.461 24.51C243.592 24.538 243.69 24.566 243.755 24.594V26.204H243.237C242.948 26.204 242.691 26.246 242.467 26.33C242.243 26.4047 242.052 26.526 241.893 26.694C241.744 26.862 241.632 27.072 241.557 27.324C241.483 27.576 241.445 27.87 241.445 28.206V32H239.737ZM247.537 32.168C246.753 32.168 246.1 32.0327 245.577 31.762C245.054 31.482 244.662 31.0573 244.401 30.488C244.14 29.9187 244.009 29.1953 244.009 28.318C244.009 27.4313 244.14 26.708 244.401 26.148C244.662 25.5787 245.054 25.1587 245.577 24.888C246.1 24.608 246.753 24.468 247.537 24.468C248.246 24.468 248.839 24.6033 249.315 24.874C249.8 25.1353 250.164 25.5413 250.407 26.092C250.65 26.6427 250.771 27.3567 250.771 28.234V28.738H245.759C245.778 29.1953 245.848 29.5827 245.969 29.9C246.09 30.208 246.277 30.4413 246.529 30.6C246.79 30.7493 247.131 30.824 247.551 30.824C247.766 30.824 247.966 30.796 248.153 30.74C248.34 30.684 248.503 30.6 248.643 30.488C248.783 30.3667 248.89 30.2173 248.965 30.04C249.049 29.8627 249.091 29.6573 249.091 29.424H250.771C250.771 29.8907 250.687 30.2967 250.519 30.642C250.36 30.9873 250.132 31.272 249.833 31.496C249.544 31.72 249.203 31.888 248.811 32C248.419 32.112 247.994 32.168 247.537 32.168ZM245.787 27.534H248.993C248.993 27.226 248.956 26.9647 248.881 26.75C248.816 26.5353 248.718 26.358 248.587 26.218C248.466 26.078 248.316 25.98 248.139 25.924C247.962 25.8587 247.761 25.826 247.537 25.826C247.173 25.826 246.865 25.8867 246.613 26.008C246.37 26.1293 246.184 26.316 246.053 26.568C245.922 26.82 245.834 27.142 245.787 27.534ZM256.681 34.422V31.146H256.597C256.447 31.37 256.265 31.5613 256.051 31.72C255.836 31.8693 255.589 31.9813 255.309 32.056C255.029 32.1307 254.721 32.168 254.385 32.168C253.843 32.168 253.358 32.0327 252.929 31.762C252.499 31.4913 252.163 31.0713 251.921 30.502C251.678 29.9327 251.557 29.2093 251.557 28.332C251.557 27.436 251.673 26.7033 251.907 26.134C252.149 25.5647 252.49 25.1447 252.929 24.874C253.367 24.6033 253.885 24.468 254.483 24.468C254.959 24.468 255.393 24.566 255.785 24.762C256.186 24.9487 256.503 25.2333 256.737 25.616H256.835L256.975 24.636H258.389V34.422H256.681ZM255.015 30.698C255.313 30.698 255.565 30.6467 255.771 30.544C255.985 30.4413 256.158 30.292 256.289 30.096C256.419 29.9 256.517 29.662 256.583 29.382C256.648 29.0927 256.681 28.766 256.681 28.402V28.234C256.681 27.7393 256.62 27.324 256.499 26.988C256.387 26.6427 256.209 26.3813 255.967 26.204C255.724 26.0267 255.407 25.938 255.015 25.938C254.595 25.938 254.259 26.022 254.007 26.19C253.764 26.3487 253.587 26.6007 253.475 26.946C253.372 27.282 253.321 27.7113 253.321 28.234V28.43C253.321 28.9433 253.372 29.368 253.475 29.704C253.587 30.04 253.764 30.292 254.007 30.46C254.259 30.6187 254.595 30.698 255.015 30.698Z" fill="white"/></g><defs><filter id="filter0_d_66_326" x="0" y="0" width="369" height="85" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feMorphology radius="17" operator="erode" in="SourceAlpha" result="effect1_dropShadow_66_326"/><feOffset dy="18"/><feGaussianBlur stdDeviation="15"/><feColorMatrix type="matrix" values="0 0 0 0 0.0677083 0 0 0 0 0.224375 0 0 0 0 0.3125 0 0 0 0.37 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_66_326"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_66_326" result="shape"/></filter><linearGradient id="paint0_linear_66_326" x1="13" y1="54" x2="290.242" y2="-111.158" gradientUnits="userSpaceOnUse"><stop stop-color="#00C8AF"/><stop offset="0.41" stop-color="#015AD7"/><stop offset="0.965"/></linearGradient><linearGradient id="paint1_linear_66_326" x1="118.759" y1="26.9319" x2="128.784" y2="26.9319" gradientUnits="userSpaceOnUse"><stop stop-color="#4083E1"/><stop offset="0.08" stop-color="#3E8BDD"/><stop offset="0.48" stop-color="#36B1CC"/><stop offset="0.8" stop-color="#31C9C1"/><stop offset="1" stop-color="#30D2BE"/></linearGradient><linearGradient id="paint2_linear_66_326" x1="116.655" y1="27.0389" x2="131.345" y2="27.0389" gradientUnits="userSpaceOnUse"><stop stop-color="#80ACEB"/><stop offset="0.3" stop-color="#7BC0E1"/><stop offset="0.73" stop-color="#76D8D7"/><stop offset="1" stop-color="#75E1D4"/></linearGradient><linearGradient id="paint3_linear_66_326" x1="114.324" y1="27.0389" x2="133.676" y2="27.0389" gradientUnits="userSpaceOnUse"><stop stop-color="#BFD6F5"/><stop offset="0.55" stop-color="#BBE7ED"/><stop offset="1" stop-color="#BAF0E9"/></linearGradient><radialGradient id="paint4_radial_66_326" cx="0" cy="0" r="1" gradientTransform="matrix(11.9853 11.4329 -86.118 86.5187 123.848 29.6448)" gradientUnits="userSpaceOnUse"><stop stop-color="#40E0C7"/><stop offset="0.304248" stop-color="#0050C8"/><stop offset="0.623256" stop-color="white"/></radialGradient></defs></svg>
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
                            <a href="{{ $merchant->fallback_payment_url }}" target="_blank" rel="noopener" onclick="return openCardFallback(this)"
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

                @elseif($invoice->status->value === 10 && ($invoice->link_type ?? 'personal') !== 'open')
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

        function openCardFallback(link) {
                var base   = link.getAttribute('href');
                var name   = document.querySelector('[name="customer_name"]')?.value || '';
                var email  = document.querySelector('[name="customer_email"]')?.value || '';
                var mobile = document.querySelector('[name="customer_mobile"]')?.value || '';
                var sep    = base.includes('?') ? '&' : '?';
                var url    = base + sep
                    + 'customer_name='    + encodeURIComponent(name)
                    + '&customer_email='  + encodeURIComponent(email)
                    + '&customer_mobile=' + encodeURIComponent(mobile);
                document.querySelectorAll('[name^="custom_fields["]').forEach(function(input) {
                    if (input.value) url += '&' + encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value);
                });
                window.open(url, '_blank', 'noopener');
                return false;
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
