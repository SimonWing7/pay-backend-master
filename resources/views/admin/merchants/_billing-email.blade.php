{{-- Billing email with a "same as notifications email" tick-box.
     Ticked = nothing is stored, so billing keeps following the Notification
     Email (or Support / login email) automatically. Expects: $merchant (nullable). --}}
@php
    $billingSame = count((array) old()) > 0
        ? (bool) old('billing_email_same')
        : empty($merchant?->billing_email);
@endphp
<div class="mb-5">
    <label class="form-label">Billing Email <span class="text-gray-400 font-normal">(statements and card charge results)</span></label>

    <label class="flex items-center gap-2 mb-2 cursor-pointer">
        <input type="checkbox" name="billing_email_same" id="billing_email_same" value="1" {{ $billingSame ? 'checked' : '' }}
            class="h-4 w-4 rounded" style="accent-color: #3d01bd;">
        <span class="text-sm text-gray-700">Same as the Reminder / Notifications Email above</span>
    </label>

    <div id="billing_email_wrap" style="{{ $billingSame ? 'display:none;' : '' }}">
        <input type="email" name="billing_email" id="billing_email"
            value="{{ old('billing_email', $merchant?->billing_email) }}"
            class="form-input @error('billing_email') border-red-400 @enderror"
            placeholder="finance@theirbusiness.com" {{ $billingSame ? 'disabled' : '' }}>
    </div>

    <p class="text-xs text-gray-400 mt-1">
        @if($merchant)
            Currently sent to: <span class="font-medium text-gray-500">{{ $merchant->billingRecipient() }}</span>.
        @endif
        If the box is ticked and the notifications email is empty, the Support Email is used, then the merchant's login email.
    </p>
    @error('billing_email')
        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
    @enderror
</div>
<script>
    (function () {
        var box = document.getElementById('billing_email_same');
        var wrap = document.getElementById('billing_email_wrap');
        var input = document.getElementById('billing_email');
        box.addEventListener('change', function () {
            wrap.style.display = box.checked ? 'none' : '';
            input.disabled = box.checked;
            if (!box.checked) { input.focus(); }
        });
    })();
</script>
