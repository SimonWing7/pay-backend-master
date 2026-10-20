<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Who is the authorised contact?</h1>
<p class="text-sm mb-6" style="color:#6b7280;">The person responsible for this account, for example an owner, director or finance manager. We may contact them to confirm your details.</p>

@include('merchant.onboarding._field', ['name' => 'signatory_name', 'label' => 'Full name', 'required' => true])
@include('merchant.onboarding._field', ['name' => 'signatory_role', 'label' => 'Role', 'required' => true, 'placeholder' => 'e.g. Managing Director'])
@include('merchant.onboarding._field', ['name' => 'signatory_mobile', 'label' => 'Mobile number', 'required' => true, 'placeholder' => '+971 50 123 4567'])
@include('merchant.onboarding._field', ['name' => 'signatory_email', 'label' => 'Email', 'type' => 'email', 'required' => true])

<div class="mt-7 mb-3">
    <p class="text-sm font-bold" style="color:#111827;">Supporting documents <span style="color:#9ca3af;font-weight:400;">(optional)</span></p>
    <p class="text-xs" style="color:#6b7280;">These can speed up the review. PDF, JPG or PNG, up to 5 MB each.</p>
</div>

<div class="mb-5">
    <label for="signatory_id" class="form-label">Emirates ID or passport of the authorised contact</label>
    @if($onboarding->signatory_id_path)<p class="text-xs mb-1" style="color:#047857;"><i class="fas fa-check-circle"></i> Uploaded. Choose a new file to replace it.</p>@endif
    <input type="file" name="signatory_id" id="signatory_id" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;">
    @error('signatory_id')<p class="field-error">{{ $message }}</p>@enderror
</div>

<div class="mb-2">
    <label for="bank_letter" class="form-label">Bank letter or statement showing the IBAN</label>
    @if($onboarding->bank_letter_path)<p class="text-xs mb-1" style="color:#047857;"><i class="fas fa-check-circle"></i> Uploaded. Choose a new file to replace it.</p>@endif
    <input type="file" name="bank_letter" id="bank_letter" accept=".pdf,.jpg,.jpeg,.png" class="form-input" style="padding:8px;">
    @error('bank_letter')<p class="field-error">{{ $message }}</p>@enderror
</div>
