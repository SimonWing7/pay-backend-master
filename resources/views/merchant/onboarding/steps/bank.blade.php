<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Where should we pay you?</h1>
<p class="text-sm mb-6" style="color:#6b7280;">Payments collected from your payers are settled into this account. Please double-check it, as a wrong IBAN delays your money.</p>

@include('merchant.onboarding._field', ['name' => 'account_holder_name', 'label' => 'Account holder name', 'required' => true, 'hint' => 'Exactly as it appears on the bank account, usually your registered company name.'])
@include('merchant.onboarding._field', ['name' => 'bank_name', 'label' => 'Bank name', 'required' => true, 'placeholder' => 'e.g. Emirates NBD'])
@include('merchant.onboarding._field', ['name' => 'iban', 'label' => 'IBAN', 'required' => true, 'placeholder' => 'AE07 0331 2345 6789 0123 456', 'hint' => 'A UAE IBAN starts with AE and has 23 characters. Spaces are fine.'])

<div class="rounded-xl p-4 text-xs" style="background:#eff6ff;color:#1e40af;">
    <i class="fas fa-lock"></i> Only Edfundo staff can see your bank details.
</div>
