@extends('merchant.layout')

@section('title', 'Settings — Business Profile')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your account and business details')

@section('content')

@include('merchant.settings._tabs')

<div class="max-w-lg">
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Business Profile</h3>

        <form method="POST" action="{{ route('merchant.settings.profile.post') }}" enctype="multipart/form-data">
            @csrf

            {{-- Trading Name --}}
            <div class="mb-5">
                <label for="merchant_trading_name" class="form-label">
                    Trading Name <span class="text-red-400">*</span>
                </label>
                <input type="text" name="merchant_trading_name" id="merchant_trading_name" required
                    value="{{ old('merchant_trading_name', $merchant->merchant_trading_name ?? $merchant->name) }}"
                    class="form-input @error('merchant_trading_name') border-red-400 @enderror"
                    placeholder="e.g. Sunshine Swimming Academy">
                <p class="text-xs text-gray-400 mt-1">This is the name shown to payers on your payment pages.</p>
                @error('merchant_trading_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Support Email --}}
            <div class="mb-5">
                <label for="support_email" class="form-label">Support Email</label>
                <input type="email" name="support_email" id="support_email"
                    value="{{ old('support_email', $merchant->support_email) }}"
                    class="form-input @error('support_email') border-red-400 @enderror"
                    placeholder="payments@yourbusiness.com">
                <p class="text-xs text-gray-400 mt-1">Shown to payers if they need to contact you about a payment.</p>
                @error('support_email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Support Phone --}}
            <div class="mb-5">
                <label for="support_phone" class="form-label">Support Phone</label>
                <input type="text" name="support_phone" id="support_phone"
                    value="{{ old('support_phone', $merchant->support_phone) }}"
                    class="form-input @error('support_phone') border-red-400 @enderror"
                    placeholder="+971 50 000 0000">
                @error('support_phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Website --}}
            <div class="mb-6">
                <label for="website" class="form-label">Website</label>
                <input type="url" name="website" id="website"
                    value="{{ old('website', $merchant->website) }}"
                    class="form-input @error('website') border-red-400 @enderror"
                    placeholder="https://www.yourbusiness.com">
                @error('website')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Webhook URL --}}
            <div class="mb-6">
                <label for="webhook_url" class="form-label">Webhook URL</label>
                <input type="url" name="webhook_url" id="webhook_url"
                    value="{{ old('webhook_url', $merchant->webhook_url) }}"
                    class="form-input @error('webhook_url') border-red-400 @enderror"
                    placeholder="https://yourserver.com/webhooks/edfundo">
                <p class="text-xs text-gray-400 mt-1">Edfundo will POST payment events to this URL. Leave blank to disable.</p>
                @error('webhook_url')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Business Logo --}}
            <div class="mb-6">
                <label class="form-label">Business Logo</label>
                @if($merchant->logo_path)
                    <div class="mb-3 flex items-center gap-4">
                        <img src="{{ $merchant->logo_url }}" alt="Current logo" style="width:64px;height:64px;object-fit:contain;border-radius:10px;border:1.5px solid #e5e7eb;padding:4px;background:#f9fafb;">
                        <div>
                            <p class="text-xs font-medium text-gray-600">Current logo</p>
                            <p class="text-xs text-gray-400 mt-0.5">Upload a new file to replace it.</p>
                        </div>
                    </div>
                @endif
                <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                    class="form-input @error('logo') border-red-400 @enderror"
                    style="padding:6px 10px;">
                <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG or WebP · Max 2 MB · Square or landscape works best.</p>
                @error('logo')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="btn-primary w-full justify-center">
                <i class="fas fa-save"></i> Save Profile
            </button>
        </form>
    </div>

    {{-- Payment Fallback --}}
    <div class="card p-6 mt-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Payment Fallback</h3>
        <p class="text-xs text-gray-400 mb-5">Shown to payers who tap "My bank is not listed yet" on your payment page. Choose how they can pay alternatively.</p>

        <form method="POST" action="{{ route('merchant.settings.profile.post') }}">
            @csrf
            <input type="hidden" name="_section" value="fallback">
            <input type="hidden" name="merchant_trading_name" value="{{ $merchant->merchant_trading_name ?? $merchant->name }}">
            <input type="hidden" name="support_email" value="{{ $merchant->support_email }}">
            <input type="hidden" name="support_phone" value="{{ $merchant->support_phone }}">
            <input type="hidden" name="website" value="{{ $merchant->website }}">
            <input type="hidden" name="webhook_url" value="{{ $merchant->webhook_url }}">

            {{-- Fallback type selector --}}
            <div class="mb-5 space-y-2">
                @foreach([''=>['None','Show your support email/phone only'],'payment_gateway'=>['Payment Gateway','Redirect payers to a card payment link (Stripe, PayTabs, etc.)'],'bank_transfer'=>['Bank Transfer Details','Show your IBAN and account details for manual transfer']] as $val=>$labels)
                @php $checked = old('fallback_type', $merchant->fallback_type ?? '') === $val; @endphp
                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ $checked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-200' }}">
                    <input type="radio" name="fallback_type" value="{{ $val }}" class="mt-0.5 flex-shrink-0" style="accent-color:#3d01bd;"
                        {{ $checked ? 'checked' : '' }} onchange="switchFallback(this.value)">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $labels[0] }}</p>
                        <p class="text-xs text-gray-400">{{ $labels[1] }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            {{-- Payment gateway URL --}}
            <div id="panel-gateway" class="{{ old('fallback_type', $merchant->fallback_type) === 'payment_gateway' ? '' : 'hidden' }} mb-5">
                <label class="form-label">Payment Gateway URL</label>
                <input type="url" name="fallback_payment_url"
                    value="{{ old('fallback_payment_url', $merchant->fallback_payment_url) }}"
                    class="form-input" placeholder="https://buy.stripe.com/your-link">
                <p class="text-xs text-gray-400 mt-1">Payers will be redirected here to pay by card.</p>
            </div>

            {{-- Bank transfer details --}}
            <div id="panel-transfer" class="{{ old('fallback_type', $merchant->fallback_type) === 'bank_transfer' ? '' : 'hidden' }} space-y-4 mb-5">
                <div>
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="fallback_bank_name"
                        value="{{ old('fallback_bank_name', $merchant->fallback_bank_name) }}"
                        class="form-input" placeholder="e.g. Emirates NBD">
                </div>
                <div>
                    <label class="form-label">Account Name</label>
                    <input type="text" name="fallback_account_name"
                        value="{{ old('fallback_account_name', $merchant->fallback_account_name) }}"
                        class="form-input" placeholder="e.g. Sunshine Swimming Academy LLC">
                </div>
                <div>
                    <label class="form-label">IBAN</label>
                    <div class="form-input font-mono text-sm" style="background:#f9fafb;color:#6b7280;">
                        {{ $merchant->iban ?? 'Not set — contact Edfundo to configure your IBAN' }}
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Your IBAN is managed by Edfundo. Contact support to update it.</p>
                </div>
                <div>
                    <label class="form-label">Reference Instructions</label>
                    <textarea name="fallback_reference_note" rows="2" class="form-input"
                        placeholder="e.g. Please use your child's full name as the payment reference">{{ old('fallback_reference_note', $merchant->fallback_reference_note) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">Shown to payers so they know what reference to include in their transfer.</p>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <i class="fas fa-save"></i> Save Fallback Settings
            </button>
        </form>
    </div>

    {{-- Webhook secret --}}
    @if($merchant->webhook_secret)
    <div class="card p-6 mt-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Webhook Secret</h3>
        <p class="text-xs text-gray-400 mb-4">Add this to your server as <code class="px-1 py-0.5 rounded text-xs" style="background:#f3f4f6;">EDFUNDO_WEBHOOK_SECRET</code>. Use it to verify the <code class="px-1 py-0.5 rounded text-xs" style="background:#f3f4f6;">X-Edfundo-Signature</code> header on incoming webhook requests.</p>
        <div class="flex items-center gap-3 mb-4">
            <code class="flex-1 px-4 py-3 rounded-lg text-xs font-mono break-all"
                style="background:#f8f9fc;color:#1c1917;border:1px solid #eef0f5;"
                id="webhookSecretValue">{{ $merchant->webhook_secret }}</code>
            <button type="button" onclick="copyWebhookSecret()" id="copySecretBtn"
                class="btn-secondary flex-shrink-0 text-sm py-2 px-4">
                <i class="fas fa-copy"></i> Copy
            </button>
        </div>
        <form method="POST" action="{{ route('merchant.settings.profile.regenerate-webhook-secret') }}"
            onsubmit="return confirm('Regenerate webhook secret? Your server will stop receiving verified webhooks until you update it with the new value.')">
            @csrf
            <button type="submit" class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;">
                <i class="fas fa-sync-alt mr-1"></i> Regenerate Secret
            </button>
        </form>
    </div>
    @endif

    {{-- Read-only account info --}}
    <div class="card p-6 mt-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Account Details</h3>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Account Name</span>
                <span class="text-sm font-medium text-gray-800">{{ $merchant->name }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Login Email</span>
                <span class="text-sm font-medium text-gray-800">{{ $merchant->email }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Account Status</span>
                <span class="badge-success">Active</span>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-4">To change your login email or account name, please contact Edfundo support.</p>
    </div>
</div>

@push('scripts')
<script>
function switchFallback(val) {
    document.getElementById('panel-gateway').classList.toggle('hidden', val !== 'payment_gateway');
    document.getElementById('panel-transfer').classList.toggle('hidden', val !== 'bank_transfer');
}

function copyWebhookSecret() {
    const val = document.getElementById('webhookSecretValue').textContent.trim();
    const btn = document.getElementById('copySecretBtn');
    navigator.clipboard.writeText(val).then(() => {
        btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2500);
    });
}
</script>
@endpush

@endsection
