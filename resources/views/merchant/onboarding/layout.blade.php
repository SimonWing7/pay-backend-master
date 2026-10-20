<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Set up your account') - Edfundo Pay</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f5f6fa; min-height: 100vh; color: #111827; }
        .btn-gradient { background: linear-gradient(135deg, #3d01bd, #00bdff); color: white; font-weight: 700; padding: 12px 28px; border-radius: 10px; border: none; cursor: pointer; font-size: 15px; transition: opacity 0.15s; display: inline-block; text-decoration: none; text-align: center; }
        .btn-gradient:hover { opacity: 0.9; }
        /* The shared billing card form uses the merchant portal's button class, which this standalone page doesn't load. */
        .btn-primary { background: linear-gradient(135deg, #3d01bd, #00bdff); color: white; font-weight: 700; padding: 12px 24px; border-radius: 10px; border: none; cursor: pointer; font-size: 15px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; transition: opacity 0.15s; }
        .btn-primary:hover { opacity: 0.9; }
        @media (max-width: 640px) { .btn-primary { width: 100%; } }
        .btn-ghost { background: transparent; color: #4b5563; font-weight: 600; padding: 12px 18px; border-radius: 10px; border: 1.5px solid #e2e5ef; cursor: pointer; font-size: 14px; display: inline-block; text-decoration: none; }
        .btn-ghost:hover { border-color: #c7cbe0; background: #fff; }
        .btn-link { background: none; border: none; color: #6b7280; font-weight: 600; font-size: 14px; cursor: pointer; padding: 12px 6px; text-decoration: underline; }
        .btn-link:hover { color: #3d01bd; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-input { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e5ef; border-radius: 8px; font-size: 14px; color: #111827; outline: none; background: #fff; transition: border-color 0.15s; }
        .form-input:focus { border-color: #3d01bd; }
        .field-error { color: #dc2626; font-size: 12px; margin-top: 4px; }
        .hint { color: #9ca3af; font-size: 12px; margin-top: 4px; }
        .dot { width: 28px; height: 28px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
    </style>
</head>
<body>
@php
    $svc = app(\App\Services\OnboardingService::class);
    $form = \App\Services\OnboardingService::FORM_STEPS;
    $pos = array_search($step ?? 'welcome', $form, true);
@endphp
<div style="max-width: 640px; margin: 0 auto; padding: 28px 16px 56px;">

    <div class="flex items-center justify-between mb-8">
        <img src="/images/edfundo-logo-colour.png" alt="Edfundo Pay" style="height: 34px; width: auto;">
        <a href="{{ route('merchant.dashboard') }}" class="text-sm font-semibold" style="color:#6b7280;">Skip for now &rarr;</a>
    </div>

    @if(isset($step) && $pos !== false)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            @foreach($form as $i => $s)
                @php $done = $svc->isStepComplete($onboarding, $s); $current = $s === $step; @endphp
                <a href="{{ route('merchant.onboarding.show', $s) }}" title="{{ \App\Services\OnboardingService::TITLES[$s] }}"
                   class="dot" style="{{ $current ? 'background:#3d01bd;color:#fff;' : ($done ? 'background:#d1fae5;color:#047857;' : 'background:#e5e7eb;color:#6b7280;') }}">
                    @if($done && !$current)&#10003;@else{{ $i + 1 }}@endif
                </a>
                @if(!$loop->last)<div style="flex:1;height:2px;background:{{ $done ? '#a7f3d0' : '#e5e7eb' }};"></div>@endif
            @endforeach
        </div>
        <p class="text-xs font-semibold uppercase tracking-wide" style="color:#9ca3af;">Step {{ $pos + 1 }} of {{ count($form) }}</p>
    </div>
    @endif

    @if($onboarding->merchant->onboarding_status === 'changes_requested' && $onboarding->review_note)
        <div class="mb-6 rounded-xl p-4 text-sm" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;">
            <p class="font-bold mb-1"><i class="fas fa-comment-dots"></i> Edfundo asked for a few changes</p>
            <p style="white-space: pre-line;">{{ $onboarding->review_note }}</p>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 rounded-xl p-4 text-sm" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 rounded-xl p-4 text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="mb-6 rounded-xl p-4 text-sm" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;">{{ session('info') }}</div>
    @endif

    <div style="background:#fff;border-radius:16px;padding:36px;box-shadow:0 4px 24px rgba(0,0,38,0.08);border:1px solid #eef0f5;">
        @yield('content')
    </div>

    <p class="text-center text-xs mt-6" style="color:#9ca3af;">
        Your answers are saved when you press a button. You can leave at any time and pick up where you stopped.
    </p>
</div>
</body>
</html>
