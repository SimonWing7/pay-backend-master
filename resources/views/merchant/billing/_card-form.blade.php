{{-- Consent + start of card registration. Expects: $card (nullable active card), $withTopBorder --}}
<form method="POST" action="{{ route('merchant.billing.card.start') }}" class="{{ $withTopBorder ? 'border-t border-gray-100 pt-4' : '' }}">
    @csrf
    <label class="flex items-start gap-2 mb-3 cursor-pointer">
        <input type="checkbox" name="consent" value="1" class="mt-1 h-4 w-4 rounded" style="accent-color:#3d01bd;" {{ old('consent') ? 'checked' : '' }}>
        <span class="text-xs text-gray-600 leading-relaxed">
            I authorise Edfundo to charge this card each month for the Edfundo Pay fees shown on my statements,
            and to retry if a payment is declined. I can replace the card at any time.
        </span>
    </label>
    @error('consent')
        <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
    @enderror
    <button type="submit" class="btn-primary"><i class="fas fa-lock"></i> {{ $card ? 'Continue to Telr' : 'Add card' }}</button>
    <p class="text-xs text-gray-400 mt-2">
        You'll enter your card on Telr's secure page and confirm with 3-D Secure. A AED {{ number_format(config('billing.verification_amount', 1), 2) }}
        verification charge is made when you add your card, and we'll return it to you. We never see or store your card number.
    </p>
</form>
