<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Your business registration</h1>
<p class="text-sm mb-6" style="color:#6b7280;">These come from your trade licence. We need them to set up your payouts, and to confirm who you are.</p>

<div class="rounded-xl p-4 mb-6 text-sm" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
    <i class="fas fa-file-lines"></i> Have your <strong>trade licence</strong> open. You can <strong>Save &amp; finish later</strong> and come back if you need to find it.
</div>

@include('merchant.onboarding._field', ['name' => 'trade_license_number', 'label' => 'Trade licence number', 'required' => true, 'placeholder' => 'e.g. 123456', 'hint' => 'Exactly as printed on your trade licence.'])
@include('merchant.onboarding._field', ['name' => 'address', 'label' => 'Registered business address', 'required' => true, 'hint' => 'The address on your trade licence.', 'autocomplete' => 'street-address'])
@include('merchant.onboarding._field', ['name' => 'city', 'label' => 'City', 'required' => true, 'placeholder' => 'e.g. Dubai'])
