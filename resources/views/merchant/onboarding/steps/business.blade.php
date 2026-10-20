<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Tell us about your business</h1>
<p class="text-sm mb-6" style="color:#6b7280;">This is what your payers see on payment pages and emails.</p>

@include('merchant.onboarding._field', ['name' => 'trading_name', 'label' => 'Business name', 'required' => true, 'hint' => 'The name your payers know you by, e.g. "Pinnakle Rugby Academy".'])
@include('merchant.onboarding._field', ['name' => 'support_email', 'label' => 'Support email', 'type' => 'email', 'required' => true, 'hint' => 'Payers can reply to this address with questions.'])
@include('merchant.onboarding._field', ['name' => 'support_phone', 'label' => 'Support phone', 'required' => true, 'placeholder' => '+971 50 123 4567'])
@include('merchant.onboarding._field', ['name' => 'website', 'label' => 'Website', 'type' => 'url', 'placeholder' => 'https://'])

<div class="mb-2">
    <label for="logo" class="form-label">Logo <span style="color:#9ca3af;font-weight:400;">(optional)</span></label>
    @if(!empty($answers['logo_path']))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($answers['logo_path']) }}" alt="Current logo" style="height:48px;width:auto;margin-bottom:8px;border-radius:6px;">
    @endif
    <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="form-input" style="padding:8px;">
    <p class="hint">PNG, JPG, SVG or WebP, up to 2 MB.</p>
    @error('logo')<p class="field-error">{{ $message }}</p>@enderror
</div>
