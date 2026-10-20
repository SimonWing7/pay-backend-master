@php
    $billingLive = app(\App\Services\OnboardingService::class)->cardRequired();
@endphp
<h1 class="text-2xl font-extrabold mb-2" style="color:#000026;">Welcome, {{ $onboarding->merchant->merchant_trading_name ?: $onboarding->merchant->name }}</h1>
<p class="text-sm mb-6" style="color:#6b7280;line-height:1.6;">Let's get your account ready to collect payments. It's {{ count(\App\Services\OnboardingService::FORM_STEPS) }} short steps, one at a time. <strong>Don't have everything to hand? No problem.</strong> Press <strong>Save &amp; finish later</strong> at any point and you'll pick up exactly where you left off.</p>

<div class="rounded-xl p-5 mb-6" style="background:#fffbeb;border:1px solid #fde68a;">
    <p class="text-sm font-extrabold mb-3" style="color:#92400e;"><i class="fas fa-clipboard-list"></i> Please have these ready</p>
    <ul class="text-sm space-y-2" style="color:#78350f;">
        <li><i class="fas fa-file-lines" style="width:20px;"></i> <strong>Your trade licence</strong>: the licence number and your registered address</li>
        <li><i class="fas fa-building-columns" style="width:20px;"></i> <strong>Your business bank details</strong>: IBAN, account number and SWIFT code. A bank statement, your banking app or a letter from your bank has them</li>
        <li><i class="fas fa-user-check" style="width:20px;"></i> <strong>Details of an authorised contact</strong>: name, role, mobile and email. Optionally a photo or scan of their Emirates ID or passport</li>
        <li><i class="fas fa-image" style="width:20px;"></i> <strong>Your logo</strong> <span style="opacity:.8;">(optional)</span></li>
        @if($billingLive)
        <li><i class="fas fa-credit-card" style="width:20px;"></i> <strong>A bank card</strong> for the monthly Edfundo fees</li>
        @endif
    </ul>
</div>

<div class="space-y-3">
    @foreach([
        ['fa-store', 'Your business', 'The name, contact details and logo your payers will see.'],
        ['fa-file-signature', 'Business registration', 'Trade licence number and address.'],
        ['fa-building-columns', 'Where we pay you', 'The bank account your payments are settled into.'],
        ['fa-user-check', 'Authorised contact', 'Who is responsible for this account.'],
        ['fa-handshake', 'Fees & agreement', 'How our fees work, and your agreement.'],
    ] as $i => $item)
    <div class="flex items-start gap-4 p-4 rounded-xl" style="background:#f9fafb;border:1px solid #f3f4f6;">
        <div class="dot" style="background:#ede9fe;color:#3d01bd;flex-shrink:0;"><i class="fas {{ $item[0] }}" style="font-size:12px;"></i></div>
        <div>
            <p class="text-sm font-bold" style="color:#111827;">{{ $i + 1 }}. {{ $item[1] }}</p>
            <p class="text-xs" style="color:#6b7280;">{{ $item[2] }}</p>
        </div>
    </div>
    @endforeach
</div>

<p class="text-xs mt-5" style="color:#9ca3af;">When you're done, Edfundo reviews your details. You can set up products and payers while you wait; payment links go live once you're approved.</p>
