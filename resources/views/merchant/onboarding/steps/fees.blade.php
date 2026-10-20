@if(app(\App\Services\OnboardingService::class)->cardRequired())
    {{-- The agreement is given with the card authorisation above; nothing more to tick here. --}}
    @if($onboarding->merchant->activeBillingCard)
        <p class="text-sm" style="color:#6b7280;">All done on this step. Press <strong>Save &amp; continue</strong> to review your details.</p>
    @endif
@else
@php $termsUrl = config('onboarding.terms_url'); @endphp
<label class="flex items-start gap-3 cursor-pointer p-4 rounded-xl" style="background:#f9fafb;border:1.5px solid {{ $errors->has('terms') ? '#f87171' : '#e5e7eb' }};">
    <input type="checkbox" name="terms" value="1" class="mt-1 h-4 w-4" style="accent-color:#3d01bd;" {{ old('terms', $onboarding->terms_accepted_at ? '1' : null) ? 'checked' : '' }}>
    <span class="text-sm" style="color:#374151;line-height:1.5;">
        I agree to the Edfundo Pay fees described above
        @if($termsUrl) and to the <a href="{{ $termsUrl }}" target="_blank" rel="noopener" style="color:#3d01bd;text-decoration:underline;">merchant terms</a>@endif,
        and I'm authorised to accept them for {{ $onboarding->merchant->merchant_trading_name ?: $onboarding->merchant->name }}.
    </span>
</label>
@error('terms')<p class="field-error">{{ $message }}</p>@enderror
@if($onboarding->terms_accepted_at)
    <p class="hint">Accepted on {{ $onboarding->terms_accepted_at->copy()->setTimezone('Asia/Dubai')->format('j F Y, H:i') }}.</p>
@endif
@endif
