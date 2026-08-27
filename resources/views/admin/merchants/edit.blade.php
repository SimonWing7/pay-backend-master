@extends('admin.layout')

@section('title', 'Edit Merchant')
@section('page-title', 'Edit Merchant')
@section('page-subtitle', 'Update merchant account details')

@section('topbar-actions')
    <a href="{{ route('admin.merchants.show', $merchant->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6 mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Account Details</h3>
        <form method="POST" action="{{ route('admin.merchants.update', $merchant->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="name" class="form-label">Full Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $merchant->name) }}" required
                    class="form-input @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="merchant_trading_name" class="form-label">Trading Name <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="merchant_trading_name" id="merchant_trading_name" value="{{ old('merchant_trading_name', $merchant->merchant_trading_name) }}"
                    class="form-input @error('merchant_trading_name') border-red-400 @enderror"
                    placeholder="e.g. Sunshine Swimming Academy">
                <p class="text-xs text-gray-400 mt-1">Shown to payers on payment pages. Falls back to the account name if blank.</p>
                @error('merchant_trading_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="email" class="form-label">Login Email <span class="text-red-400">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email', $merchant->email) }}" required
                    class="form-input @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="form-label">New Password <span class="text-gray-400 font-normal">(leave blank to keep current)</span></label>
                <input type="password" name="password" id="password"
                    class="form-input @error('password') border-red-400 @enderror">
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Business Profile</h3>

            <div class="mb-5">
                <label for="support_email" class="form-label">Support Email</label>
                <input type="email" name="support_email" id="support_email"
                    value="{{ old('support_email', $merchant->support_email) }}"
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
                    value="{{ old('support_phone', $merchant->support_phone) }}"
                    class="form-input @error('support_phone') border-red-400 @enderror"
                    placeholder="+971 50 000 0000">
                @error('support_phone')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="receipt_cc_email" class="form-label">Receipt Copy Email <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="email" name="receipt_cc_email" id="receipt_cc_email"
                    value="{{ old('receipt_cc_email', $merchant->receipt_cc_email) }}"
                    class="form-input @error('receipt_cc_email') border-red-400 @enderror"
                    placeholder="customercare@theirbusiness.com">
                <p class="text-xs text-gray-400 mt-1">If set, every customer payment receipt is also BCC'd here for the merchant's own records.</p>
                @error('receipt_cc_email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="website" class="form-label">Website</label>
                <input type="url" name="website" id="website"
                    value="{{ old('website', $merchant->website) }}"
                    class="form-input @error('website') border-red-400 @enderror"
                    placeholder="https://www.theirbusiness.com">
                @error('website')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="webhook_url" class="form-label">Webhook URL</label>
                <input type="url" name="webhook_url" id="webhook_url"
                    value="{{ old('webhook_url', $merchant->webhook_url) }}"
                    class="form-input @error('webhook_url') border-red-400 @enderror"
                    placeholder="https://theirserver.com/webhooks/edfundo">
                <p class="text-xs text-gray-400 mt-1">Setting this will auto-generate a webhook secret if one doesn't exist yet.</p>
                @error('webhook_url')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Payment Fallback</h3>
            <p class="text-xs text-gray-400 mb-5">Shown to payers whose bank is not available in the Open Banking flow.</p>

            <div class="mb-5 space-y-2">
                {{-- Logo --}}
                <div class="mb-5">
                    <label class="form-label">Business Logo</label>
                    @if($merchant->logo_path)
                        <div class="mb-3 flex items-center gap-3">
                            <img src="{{ $merchant->logo_url }}" alt="Current logo" style="width:56px;height:56px;object-fit:contain;border-radius:8px;border:1.5px solid #e5e7eb;padding:4px;background:#f9fafb;">
                            <p class="text-xs text-gray-500">Current logo · Upload a new file to replace.</p>
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                        class="form-input @error('logo') border-red-400 @enderror"
                        style="padding:6px 10px;">
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, SVG or WebP · Max 2 MB.</p>
                    @error('logo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @foreach([''=>['None','Support contact only'],'payment_gateway'=>['Payment Gateway','Redirect to card payment link'],'bank_transfer'=>['Bank Transfer','Show IBAN and account details']] as $val=>$labels)
                @php $checked = old('fallback_type', $merchant->fallback_type ?? '') === $val; @endphp
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

            <div id="panel-none" class="{{ old('fallback_type', $merchant->fallback_type ?? '') === '' ? '' : 'hidden' }} mb-5">
                <label class="form-label">Custom Message (optional)</label>
                <textarea name="fallback_none_note" rows="3" class="form-input"
                    placeholder="e.g. Please pay the coach directly via debit or credit card.">{{ old('fallback_none_note', $merchant->fallback_none_note) }}</textarea>
                <p class="text-xs text-gray-400 mt-1">Shown to payers instead of the support email/phone. Leave blank to show support email/phone as usual.</p>
            </div>

            <div id="panel-gateway" class="{{ old('fallback_type', $merchant->fallback_type) === 'payment_gateway' ? '' : 'hidden' }} mb-5">
                <label class="form-label">Payment Gateway URL</label>
                <input type="url" name="fallback_payment_url"
                    value="{{ old('fallback_payment_url', $merchant->fallback_payment_url) }}"
                    class="form-input" placeholder="https://buy.stripe.com/your-link">
            </div>

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
                    <label class="form-label">Reference Instructions</label>
                    <textarea name="fallback_reference_note" rows="2" class="form-input"
                        placeholder="e.g. Please use your child's full name as the payment reference">{{ old('fallback_reference_note', $merchant->fallback_reference_note) }}</textarea>
                </div>
            </div>

            <div class="border-t border-gray-100 my-6"></div>
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Payment Configuration</h3>

            <div class="mb-5">
                <label for="iban" class="form-label">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban', $merchant->iban) }}" placeholder="AE070331234567890123456"
                    class="form-input @error('iban') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Required for payment processing</p>
                @error('iban')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="bank_name" class="form-label">Bank Name</label>
                <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $merchant->bank_name) }}" placeholder="e.g. Emirates NBD"
                    class="form-input @error('bank_name') border-red-400 @enderror">
                @error('bank_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="account_holder_name" class="form-label">Account Holder Name</label>
                <input type="text" name="account_holder_name" id="account_holder_name" value="{{ old('account_holder_name', $merchant->account_holder_name) }}" placeholder="Exact name as registered with the bank"
                    class="form-input @error('account_holder_name') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Usually the business name — confirm with the merchant, it must match their bank records exactly.</p>
                @error('account_holder_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="lean_destination_id" class="form-label">Lean Payment Destination ID</label>
                <input type="text" name="lean_destination_id" id="lean_destination_id" value="{{ old('lean_destination_id', $merchant->lean_destination_id) }}" placeholder="e.g. dst_a1b2c3d4"
                    class="form-input @error('lean_destination_id') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Created in the Lean dashboard for this merchant's bank account. If left blank, payments fall back to the platform's default destination — only correct if this merchant hasn't been given their own yet.</p>
                @error('lean_destination_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6 flex items-center gap-3 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $merchant->is_active) ? 'checked' : '' }}
                    class="h-4 w-4 rounded" style="accent-color: #3d01bd;">
                <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
                    Account active
                </label>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.merchants.show', $merchant->id) }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Legal entities — separate companies/trade licenses under this one
         merchant login (e.g. a sports academy operating in both Dubai and
         Abu Dhabi under different trade licenses but the same bank account).
         Purely optional: most merchants will never have any of these, and
         nothing changes for them if so. --}}
    <div class="card p-6 mt-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Entities</h3>
        <p class="text-xs text-gray-400 mb-4">Separate companies/trade licenses under this merchant, each with their own Lean payment destination. Optional — leave empty if this merchant only operates as one legal entity. When set, the merchant can pick an entity per Product or Payment Link.</p>

        @if($merchant->entities->count() > 0)
        <div class="space-y-2 mb-5">
            @foreach($merchant->entities as $entity)
            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200">
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $entity->name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $entity->lean_destination_id ?: 'No destination set yet' }}</p>
                </div>
                <form method="POST" action="{{ route('admin.merchants.entities.destroy', [$merchant->id, $entity->id]) }}"
                    onsubmit="return confirm('Remove this entity? Any Products/Payment Links using it will fall back to the merchant\'s default destination.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('admin.merchants.entities.store', $merchant->id) }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="entity_name" class="form-label">Entity Name</label>
                    <input type="text" name="name" id="entity_name" value="{{ old('name') }}"
                        placeholder="e.g. Pinnakle Rugby — Abu Dhabi"
                        class="form-input @error('name', 'entity') border-red-400 @enderror">
                    @error('name', 'entity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="entity_lean_destination_id" class="form-label">Lean Destination ID <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="text" name="lean_destination_id" id="entity_lean_destination_id" value="{{ old('lean_destination_id') }}"
                        placeholder="e.g. dst_a1b2c3d4"
                        class="form-input @error('lean_destination_id', 'entity') border-red-400 @enderror">
                    @error('lean_destination_id', 'entity')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit" class="btn-secondary text-sm">
                <i class="fas fa-plus"></i> Add Entity
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function switchFallback(val) {
    document.getElementById('panel-none').classList.toggle('hidden', val !== '');
    document.getElementById('panel-gateway').classList.toggle('hidden', val !== 'payment_gateway');
    document.getElementById('panel-transfer').classList.toggle('hidden', val !== 'bank_transfer');

    // Update radio label highlight
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
