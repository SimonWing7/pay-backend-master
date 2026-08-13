@extends('admin.layout')

@section('title', 'New Merchant')
@section('page-title', 'New Merchant')
@section('page-subtitle', 'Create a merchant account')

@section('topbar-actions')
    <a href="{{ route('admin.merchants.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6 mb-6">
        <form method="POST" action="{{ route('admin.merchants.store') }}" enctype="multipart/form-data">
            @csrf

            {{-- ── Account Details ── --}}
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Account Details</h3>

            <div class="mb-5">
                <label for="name" class="form-label">Full Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="form-input @error('name') border-red-400 @enderror"
                    placeholder="e.g. Al Noor School">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="merchant_trading_name" class="form-label">Trading Name <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="merchant_trading_name" id="merchant_trading_name" value="{{ old('merchant_trading_name') }}"
                    class="form-input @error('merchant_trading_name') border-red-400 @enderror"
                    placeholder="e.g. Sunshine Swimming Academy">
                <p class="text-xs text-gray-400 mt-1">Shown to payers on payment pages. Falls back to the account name if blank.</p>
                @error('merchant_trading_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="email" class="form-label">Login Email <span class="text-red-400">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="form-input @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="form-label">Password <span class="text-red-400">*</span></label>
                <input type="password" name="password" id="password" required
                    class="form-input @error('password') border-red-400 @enderror">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── Business Profile ── --}}
            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Business Profile</h3>

            <div class="mb-5">
                <label for="support_email" class="form-label">Support Email</label>
                <input type="email" name="support_email" id="support_email"
                    value="{{ old('support_email') }}"
                    class="form-input @error('support_email') border-red-400 @enderror"
                    placeholder="payments@theirbusiness.com">
                <p class="text-xs text-gray-400 mt-1">Shown to payers if they need to contact the merchant.</p>
                @error('support_email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="support_phone" class="form-label">Support Phone</label>
                <input type="text" name="support_phone" id="support_phone"
                    value="{{ old('support_phone') }}"
                    class="form-input @error('support_phone') border-red-400 @enderror"
                    placeholder="+971 50 000 0000">
                @error('support_phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="website" class="form-label">Website</label>
                <input type="url" name="website" id="website"
                    value="{{ old('website') }}"
                    class="form-input @error('website') border-red-400 @enderror"
                    placeholder="https://www.theirbusiness.com">
                @error('website')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="webhook_url" class="form-label">Webhook URL</label>
                <input type="url" name="webhook_url" id="webhook_url"
                    value="{{ old('webhook_url') }}"
                    class="form-input @error('webhook_url') border-red-400 @enderror"
                    placeholder="https://theirserver.com/webhooks/edfundo">
                <p class="text-xs text-gray-400 mt-1">Setting this will auto-generate a webhook secret.</p>
                @error('webhook_url')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- ── Payment Fallback ── --}}
            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment Fallback</h3>
            <p class="text-xs text-gray-400 mb-5">Shown to payers whose bank is not available in the Open Banking flow.</p>

            <div class="mb-5 space-y-2">
                {{-- Logo --}}
                <div class="mb-5">
                    <label class="form-label">Business Logo <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                        class="form-input @error('logo') border-red-400 @enderror"
                        style="padding:6px 10px;">
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG or WebP · Max 2 MB.</p>
                    @error('logo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @foreach([''=>['None','Support contact only'],'payment_gateway'=>['Payment Gateway','Redirect to card payment link'],'bank_transfer'=>['Bank Transfer','Show IBAN and account details']] as $val=>$labels)
                @php $checked = old('fallback_type', '') === $val; @endphp
                <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors {{ $checked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-indigo-200' }}">
                    <input type="radio" name="fallback_type" value="{{ $val }}" class="mt-0.5" style="accent-color:#3d01bd;"
                        {{ $checked ? 'checked' : '' }} onchange="switchFallback(this.value)">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $labels[0] }}</p>
                        <p class="text-xs text-gray-400">{{ $labels[1] }}</p>
                    </div>
                </label>
                @endforeach
            </div>

            <div id="panel-gateway" class="{{ old('fallback_type') === 'payment_gateway' ? '' : 'hidden' }} mb-5">
                <label class="form-label">Payment Gateway URL</label>
                <input type="url" name="fallback_payment_url"
                    value="{{ old('fallback_payment_url') }}"
                    class="form-input" placeholder="https://buy.stripe.com/your-link">
            </div>

            <div id="panel-transfer" class="{{ old('fallback_type') === 'bank_transfer' ? '' : 'hidden' }} space-y-4 mb-5">
                <div>
                    <label class="form-label">Bank Name</label>
                    <input type="text" name="fallback_bank_name"
                        value="{{ old('fallback_bank_name') }}"
                        class="form-input" placeholder="e.g. Emirates NBD">
                </div>
                <div>
                    <label class="form-label">Account Holder Name</label>
                    <input type="text" name="fallback_account_name"
                        value="{{ old('fallback_account_name') }}"
                        class="form-input" placeholder="e.g. Sunshine Swimming Academy LLC">
                </div>
                <div>
                    <label class="form-label">Reference Instructions</label>
                    <textarea name="fallback_reference_note" rows="2" class="form-input"
                        placeholder="e.g. Please use your child's full name as the payment reference">{{ old('fallback_reference_note') }}</textarea>
                </div>
            </div>

            {{-- ── Payment Configuration ── --}}
            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Payment Configuration</h3>

            <div class="mb-5">
                <label for="iban" class="form-label">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban') }}" placeholder="AE070331234567890123456"
                    class="form-input @error('iban') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Required for payment processing</p>
                @error('iban')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="category_code" class="form-label">Merchant Category Code (MCC)</label>
                <input type="text" name="category_code" id="category_code" value="{{ old('category_code') }}" placeholder="5411"
                    class="form-input @error('category_code') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Default: 5411</p>
                @error('category_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="sic_code" class="form-label">SIC Code</label>
                <input type="text" name="sic_code" id="sic_code" value="{{ old('sic_code') }}" placeholder="5411"
                    class="form-input @error('sic_code') border-red-400 @enderror">
                @error('sic_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 flex items-center gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}
                    class="h-4 w-4 rounded" style="accent-color: #3d01bd;">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                    Account active — merchant can log in immediately
                </label>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.merchants.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-store"></i> Create Merchant
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function switchFallback(val) {
    document.getElementById('panel-gateway').classList.toggle('hidden', val !== 'payment_gateway');
    document.getElementById('panel-transfer').classList.toggle('hidden', val !== 'bank_transfer');

    document.querySelectorAll('[name="fallback_type"]').forEach(function(radio) {
        var label = radio.closest('label');
        if (radio.value === val) {
            label.classList.remove('border-gray-200');
            label.classList.add('border-indigo-400', 'bg-indigo-50');
        } else {
            label.classList.remove('border-indigo-400', 'bg-indigo-50');
            label.classList.add('border-gray-200');
        }
    });
}
</script>
@endpush

@endsection
