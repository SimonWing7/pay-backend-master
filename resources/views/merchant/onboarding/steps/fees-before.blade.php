@php
    $merchant = $onboarding->merchant;
    $fee = app(\App\Services\OnboardingService::class)->currentFee($merchant);
    $billingLive = app(\App\Services\OnboardingService::class)->cardRequired();
    $card = $merchant->activeBillingCard;
@endphp
<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Fees &amp; agreement</h1>
<p class="text-sm mb-6" style="color:#6b7280;">Here is exactly how our fees work.</p>

<div class="rounded-xl p-5 mb-6" style="background:#f5f3ff;border:1px solid #ddd6fe;">
    <p class="text-3xl font-extrabold" style="color:#3d01bd;">{{ $fee }}%</p>
    <p class="text-sm mb-3" style="color:#4c1d95;">of each completed payment you collect through Edfundo Pay</p>
    <ul class="text-sm space-y-1" style="color:#374151;">
        <li><i class="fas fa-file-invoice" style="color:#a78bfa;width:18px;"></i> On the <strong>1st of each month</strong> we email you a statement for the previous month.</li>
        <li><i class="fas fa-credit-card" style="color:#a78bfa;width:18px;"></i> On the <strong>3rd</strong> we charge the card you register below.</li>
        <li><i class="fas fa-ban" style="color:#a78bfa;width:18px;"></i> Failed or abandoned payments are never charged.</li>
    </ul>
</div>

@if($billingLive)
<div class="mb-6">
    <p class="text-sm font-bold mb-2" style="color:#111827;">Add your card to agree</p>
    @if($card)
        <div class="rounded-xl p-4 text-sm" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
            <i class="fas fa-check-circle"></i> {{ $card->card_type ?: 'Card' }} ending {{ $card->last4 }} is registered, and you've agreed to the fees.
        </div>
    @else
        @error('card')
            <div class="rounded-xl p-4 mb-3 text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">{{ $message }}</div>
        @enderror
        <p class="text-xs mb-3" style="color:#6b7280;">One tick covers both the fees and the monthly card payment. You'll then enter your card on Telr's secure page.</p>
        @include('merchant.billing._card-form', ['card' => null, 'withTopBorder' => false, 'returnTo' => 'onboarding'])
    @endif
</div>
@else
<p class="text-xs mb-6" style="color:#9ca3af;"><i class="fas fa-info-circle"></i> We'll ask you to register a card before your first statement. There's nothing to add now.</p>
@endif
