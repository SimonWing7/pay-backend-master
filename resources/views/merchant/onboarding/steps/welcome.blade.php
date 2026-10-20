<h1 class="text-2xl font-extrabold mb-2" style="color:#000026;">Welcome, {{ $onboarding->merchant->merchant_trading_name ?: $onboarding->merchant->name }}</h1>
<p class="text-sm mb-6" style="color:#6b7280;line-height:1.6;">Let's get your account ready to collect payments. It takes about 5 minutes, one small step at a time. You can stop whenever you like: press <strong>Save &amp; finish later</strong> and you'll pick up exactly where you left off.</p>

<div class="space-y-3">
    @foreach([
        ['fa-store', 'Your business', 'The name, contact details and logo your payers will see.'],
        ['fa-building-columns', 'Where we pay you', 'The bank account your payments are settled into.'],
        ['fa-user-check', 'Authorised contact', 'Who is responsible for this account.'],
        ['fa-file-signature', 'Fees & agreement', 'How our fees work, and your agreement.'],
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
