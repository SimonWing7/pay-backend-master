<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Where should we pay you?</h1>
<p class="text-sm mb-4" style="color:#6b7280;">Payments collected from your payers are settled into this account. Please double-check it, as a wrong detail delays your money.</p>

<div class="rounded-xl p-4 mb-6 text-sm" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
    <i class="fas fa-building-columns"></i> You'll find all of these in your <strong>banking app</strong>, on a <strong>bank statement</strong> or on a <strong>letter from your bank</strong>. The bank account must be in your business's name.
</div>

@include('merchant.onboarding._field', ['name' => 'account_holder_name', 'label' => 'Account holder name', 'required' => true, 'hint' => 'Exactly as it appears on the bank account, usually your registered company name.'])
@include('merchant.onboarding._field', ['name' => 'bank_name', 'label' => 'Bank name', 'required' => true, 'placeholder' => 'e.g. Emirates NBD'])
@include('merchant.onboarding._field', ['name' => 'iban', 'label' => 'IBAN', 'required' => true, 'placeholder' => 'AE07 0331 2345 6789 0123 456', 'hint' => 'A UAE IBAN starts with AE and has 23 characters. Spaces are fine.'])
@include('merchant.onboarding._field', ['name' => 'account_number', 'label' => 'Account number', 'required' => true, 'hint' => 'Your bank account number, different from the IBAN. Check your statement or ask your bank.'])
@include('merchant.onboarding._field', ['name' => 'swift_code', 'label' => 'SWIFT / BIC code', 'required' => true, 'placeholder' => 'e.g. EBILAEAD', 'hint' => 'Identifies your bank: 8 or 11 letters and numbers. It is on your bank letter or on your bank\'s website.'])

<div class="rounded-xl p-4 text-xs" style="background:#eff6ff;color:#1e40af;">
    <i class="fas fa-lock"></i> Only Edfundo staff can see your bank details.
</div>
