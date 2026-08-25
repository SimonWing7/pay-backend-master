<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $product->name }} — {{ $product->merchant->merchant_trading_name ?? $product->merchant->name }}</title>
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
        .form-input {
            width: 100%; padding: 11px 14px; border: 1.5px solid #e2e5ef;
            border-radius: 8px; font-size: 14px; color: #111827;
            background: #ffffff; outline: none; transition: border-color 0.15s;
        }
        .form-input:focus { border-color: var(--purple); }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center py-10 px-4">

        {{-- Brand --}}
        <div class="mb-6 text-center">
            <div class="gradient-bar h-1 w-16 rounded-full mx-auto mb-4"></div>
            <p class="text-xs font-semibold tracking-widest uppercase" style="color:#9ca3af;">Powered by Edfundo Pay</p>
        </div>

        {{-- Product card --}}
        <div class="pay-card w-full max-w-md overflow-hidden mb-4">

            {{-- Merchant header --}}
            <div class="px-6 pt-8 pb-6 text-center border-b border-gray-100">
                @if($product->merchant->logo_path)
                    <div class="mx-auto mb-4" style="width:64px;height:64px;border-radius:14px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f9fafb;border:1.5px solid #e5e7eb;">
                        <img src="{{ $product->merchant->logo_url }}" alt="{{ $product->merchant->merchant_trading_name ?? $product->merchant->name }}" style="width:100%;height:100%;object-fit:contain;padding:6px;">
                    </div>
                @else
                    <div class="mx-auto mb-4" style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,#3d01bd,#00bdff);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-store text-white text-xl"></i>
                    </div>
                @endif
                <h1 class="text-xl font-bold" style="color:#000026;">
                    {{ $product->merchant->merchant_trading_name ?? $product->merchant->name }}
                </h1>
                <p class="text-sm text-gray-400 mt-1">Product Purchase</p>
            </div>

            {{-- Product details --}}
            <div class="px-6 py-5 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-1">{{ $product->name }}</h2>
                @if($product->description)
                <p class="text-sm text-gray-500 mb-4">{{ $product->description }}</p>
                @endif
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Price</span>
                    <span class="text-2xl font-extrabold gradient-text">AED {{ number_format($product->fee, 2) }}</span>
                </div>
            </div>

        </div>

        {{-- Customer details form --}}
        <div class="pay-card w-full max-w-md overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100">
                <h3 class="font-bold text-gray-800">Your Details</h3>
                <p class="text-sm text-gray-400 mt-1">Please provide your information to proceed</p>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
            <div class="mx-6 mt-5 p-4 rounded-xl text-sm flex items-center gap-3" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
                <i class="fas fa-check-circle text-green-400"></i>
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="mx-6 mt-5 p-4 rounded-xl text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                    <li class="flex items-center gap-2"><i class="fas fa-exclamation-circle text-red-400 text-xs"></i>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="px-6 py-5">
                <form method="POST" action="{{ route('public.product.store', $product->uuid) }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="form-label">
                            Email Address <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="email" name="email" id="email"
                            value="{{ old('email') }}" required
                            class="form-input"
                            placeholder="your@email.com">
                    </div>

                    <div class="mb-4">
                        <label for="name" class="form-label">
                            Full Name <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                            value="{{ old('name') }}" required
                            class="form-input"
                            placeholder="Your full name">
                    </div>

                    <div class="mb-6">
                        <label for="mobile_number" class="form-label">
                            Mobile Number <span style="color:#dc2626;">*</span>
                        </label>
                        <input type="text" name="mobile_number" id="mobile_number"
                            value="{{ old('mobile_number') }}" required
                            class="form-input"
                            placeholder="+971 50 000 0000">
                    </div>

                    @if($product->custom_fields && count($product->custom_fields) > 0)
                        @foreach($product->custom_fields as $field)
                        <div class="mb-4">
                            <label for="custom_field_{{ $loop->index }}" class="form-label">
                                {{ $field['label'] }}
                                @if(!empty($field['required']))
                                    <span style="color:#dc2626;">*</span>
                                @else
                                    <span class="text-gray-400 font-normal"> (optional)</span>
                                @endif
                            </label>
                            <input type="text"
                                name="custom_fields[{{ $field['label'] }}]"
                                id="custom_field_{{ $loop->index }}"
                                value="{{ old('custom_fields.' . $field['label']) }}"
                                {{ !empty($field['required']) ? 'required' : '' }}
                                placeholder="{{ $field['label'] }}"
                                class="form-input">
                        </div>
                        @endforeach
                    @endif

                    <button type="submit" class="pay-btn">
                        <i class="fas fa-arrow-right"></i>
                        Continue to Payment — AED {{ number_format($product->fee, 2) }}
                    </button>
                </form>

                <div class="mt-4 flex items-center justify-center gap-5 text-xs text-gray-400">
                    <span><i class="fas fa-lock mr-1"></i>256-bit encrypted</span>
                    <span><i class="fas fa-shield-alt mr-1"></i>UAE Central Bank regulated</span>
                </div>
            </div>
        </div>

        <p class="mt-6 text-xs text-gray-400">
            &copy; {{ date('Y') }} Edfundo Pay. All rights reserved.
        </p>
    </div>
</body>
</html>
