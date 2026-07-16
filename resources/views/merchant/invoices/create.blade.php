@extends('merchant.layout')

@section('title', 'Create Payment Link')
@section('page-title', 'Create Payment Link')
@section('page-subtitle', 'Set up a new payment link to share with payers')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

<style>
    .link-type-card {
        border: 2px solid #e2e5ef;
        border-radius: 12px;
        padding: 20px;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #ffffff;
        flex: 1;
    }
    .link-type-card:hover {
        border-color: #3d01bd;
    }
    .link-type-card.selected {
        border-color: #3d01bd;
        background: linear-gradient(135deg, rgba(61,1,189,0.04), rgba(0,189,255,0.04));
    }
    .link-type-card .icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #f0f1f5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #6b7280;
        margin-bottom: 12px;
        transition: all 0.15s ease;
    }
    .link-type-card.selected .icon-wrap {
        background: linear-gradient(135deg, #3d01bd, #00bdff);
        color: white;
    }
    .individual-section {
        display: none;
    }
    .individual-section.visible {
        display: block;
    }
    .individual-tab {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1.5px solid #e2e5ef;
        background: white;
        color: #6b7280;
    }
    .individual-tab.active {
        background: linear-gradient(135deg, #3d01bd, #00bdff);
        color: white;
        border-color: transparent;
    }
</style>

<form method="POST" action="{{ route('merchant.invoices.store') }}" id="invoiceForm">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Main form --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Product & Amount --}}
            <div class="card p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment Details</h3>

                @if($products->isNotEmpty())
                <div class="mb-5">
                    <label class="form-label">Product <span class="text-gray-400 font-normal">(optional — or enter details manually below)</span></label>
                    <select name="invoice_details[0][product_id]" id="productSelect"
                        class="form-input"
                        data-index="0"
                        onchange="onProductChange(this)">
                        <option value="">No product — enter amount manually</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}"
                                data-name="{{ $product->name }}"
                                data-fee="{{ $product->fee }}"
                                {{ old('invoice_details.0.product_id') == $product->id ? 'selected' : '' }}>
                                {{ $product->name }} — AED {{ number_format($product->fee, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('invoice_details.0.product_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                @else
                <input type="hidden" name="invoice_details[0][product_id]" value="">
                @endif

                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="form-label">Amount (AED) <span class="text-red-400">*</span></label>
                        <input type="number" step="0.01" name="invoice_details[0][fee]" id="feeInput"
                            value="{{ old('invoice_details.0.fee') }}" required min="0.01"
                            class="form-input fee-input"
                            placeholder="0.00"
                            oninput="syncTotal()">
                        <input type="hidden" name="total_fee" id="total_fee" value="{{ old('total_fee', '0.00') }}">
                        @error('invoice_details.0.fee')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Title / Description <span class="text-red-400">*</span></label>
                        <input type="text" name="invoice_details[0][title]" id="titleInput"
                            value="{{ old('invoice_details.0.title') }}" required
                            class="form-input"
                            placeholder="e.g. Term 1 Swimming Fees">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Due Date <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="date" name="due_date"
                            value="{{ old('due_date') }}"
                            class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Reference <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" name="reference"
                            value="{{ old('reference') }}"
                            class="form-input"
                            placeholder="e.g. TERM1-2025"
                            maxlength="100">
                        <p class="text-xs text-gray-400 mt-1">Your own code for reconciliation.</p>
                    </div>
                </div>
            </div>

            {{-- Link Type --}}
            <div class="card p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Link Type</h3>
                <div class="flex gap-4 mb-2">
                    <div class="link-type-card selected" id="card-open" onclick="selectLinkType('open')">
                        <div class="icon-wrap"><i class="fas fa-globe"></i></div>
                        <p class="font-semibold text-gray-800 text-sm mb-1">Open Link</p>
                        <p class="text-xs text-gray-500">Anyone with the link can pay. Great for class fees or activity fees shared with a group.</p>
                    </div>
                    <div class="link-type-card" id="card-personal" onclick="selectLinkType('personal')">
                        <div class="icon-wrap"><i class="fas fa-user"></i></div>
                        <p class="font-semibold text-gray-800 text-sm mb-1">Personal Link</p>
                        <p class="text-xs text-gray-500">For a specific individual. A unique link is generated for them.</p>
                    </div>
                </div>
                <input type="hidden" name="link_type" id="link_type" value="{{ old('link_type', 'open') }}">
                <input type="hidden" name="invoice_type" id="invoice_type" value="{{ old('invoice_type', 'open') }}">

                {{-- Individual section (shown for personal link) --}}
                <div class="individual-section mt-5" id="individualSection">
                    <div class="flex items-center gap-2 mb-4">
                        <button type="button" class="individual-tab active" id="tab-existing" onclick="switchIndividualTab('existing')">
                            Select Existing
                        </button>
                        <button type="button" class="individual-tab" id="tab-new" onclick="switchIndividualTab('new')">
                            Add New
                        </button>
                    </div>

                    {{-- Select existing individual --}}
                    <div id="panel-existing">
                        <label class="form-label">Select Individual</label>
                        <select name="consumer_id" id="consumer_id" class="form-input">
                            <option value="">Choose an individual…</option>
                            @foreach($consumers as $consumer)
                                <option value="{{ $consumer->id }}" {{ (old('consumer_id') ?? $preselectedConsumerId) == $consumer->id ? 'selected' : '' }}>
                                    {{ $consumer->name }}@if($consumer->email) ({{ $consumer->email }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('consumer_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Add new individual inline --}}
                    <div id="panel-new" style="display:none;">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="form-label">Full Name <span class="text-red-400">*</span></label>
                                <input type="text" name="new_consumer_name" value="{{ old('new_consumer_name') }}"
                                    class="form-input" placeholder="e.g. Sarah Al Mansouri">
                            </div>
                            <div>
                                <label class="form-label">Email</label>
                                <input type="email" name="new_consumer_email" value="{{ old('new_consumer_email') }}"
                                    class="form-input" placeholder="sarah@example.com">
                            </div>
                            <div>
                                <label class="form-label">Mobile Number</label>
                                <input type="text" name="new_consumer_mobile" value="{{ old('new_consumer_mobile') }}"
                                    class="form-input" placeholder="+971 50 000 0000">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Right: Summary --}}
        <div class="space-y-6">
            <div class="card p-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Summary</h3>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm text-gray-500">Amount</span>
                    <span class="font-bold text-lg gradient-text" id="summaryAmount">AED 0.00</span>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <span class="text-sm text-gray-500">Type</span>
                    <span class="text-sm font-medium text-gray-700" id="summaryType">Open Link</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-500">Individual</span>
                    <span class="text-sm font-medium text-gray-700" id="summaryIndividual">—</span>
                </div>

                <div class="border-t border-gray-100 mt-5 pt-5">
                    <button type="submit" class="btn-primary w-full justify-center text-base py-3">
                        <i class="fas fa-link"></i> Create Payment Link
                    </button>
                    <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary w-full justify-center mt-3">
                        Cancel
                    </a>
                </div>
            </div>

            <div class="card p-5" style="background: linear-gradient(135deg, rgba(61,1,189,0.04), rgba(0,189,255,0.04));">
                <p class="text-xs font-semibold text-gray-600 mb-2"><i class="fas fa-lightbulb text-amber-400 mr-1"></i> Tip</p>
                <p class="text-xs text-gray-500">Use <strong>Open Link</strong> to share one URL with a whole class or group. Use <strong>Personal Link</strong> when the payment is for a specific student or parent.</p>
            </div>
        </div>

    </div>
</form>

@push('scripts')
<script>
const products = {
    @foreach($products as $product)
    {{ $product->id }}: { name: "{{ addslashes($product->name) }}", fee: {{ $product->fee }} },
    @endforeach
};

function onProductChange(select) {
    const productId = select.value;
    if (productId && products[productId]) {
        const p = products[productId];
        const feeInput = document.getElementById('feeInput');
        const titleInput = document.getElementById('titleInput');
        if (!feeInput.value || feeInput.value === '0') feeInput.value = p.fee;
        if (!titleInput.value) titleInput.value = p.name;
        syncTotal();
    }
}

function syncTotal() {
    const fee = parseFloat(document.getElementById('feeInput').value) || 0;
    document.getElementById('total_fee').value = fee.toFixed(2);
    document.getElementById('summaryAmount').textContent = 'AED ' + fee.toFixed(2);
}

function selectLinkType(type) {
    document.getElementById('link_type').value = type;
    document.getElementById('invoice_type').value = type;

    ['open', 'personal'].forEach(t => {
        document.getElementById('card-' + t).classList.toggle('selected', t === type);
    });

    const section = document.getElementById('individualSection');
    if (type === 'personal') {
        section.classList.add('visible');
        document.getElementById('summaryType').textContent = 'Personal Link';
    } else {
        section.classList.remove('visible');
        document.getElementById('summaryType').textContent = 'Open Link';
        document.getElementById('summaryIndividual').textContent = '—';
        document.getElementById('consumer_id').value = '';
    }
}

function switchIndividualTab(tab) {
    ['existing', 'new'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        document.getElementById('panel-' + t).style.display = t === tab ? 'block' : 'none';
    });
    if (tab === 'existing') {
        document.getElementById('consumer_id').name = 'consumer_id';
    } else {
        document.getElementById('consumer_id').name = '_consumer_id_disabled';
    }
}

// Watch existing consumer select for summary
document.getElementById('consumer_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('summaryIndividual').textContent = opt.value ? opt.text : '—';
});

// Watch new individual name field for summary
document.getElementById('new_consumer_name').addEventListener('input', function() {
    document.getElementById('summaryIndividual').textContent = this.value.trim() || '—';
});

// Restore state on old() if validation failed, or pre-select from consumer profile
document.addEventListener('DOMContentLoaded', function() {
    const storedType = document.getElementById('link_type').value;
    @if($preselectedConsumerId && !old('link_type'))
        // Came from a customer profile — switch to personal and lock it in
        selectLinkType('personal');
        const sel = document.getElementById('consumer_id');
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            document.getElementById('summaryIndividual').textContent = opt.text;
        }
    @else
        if (storedType === 'personal') selectLinkType('personal');
    @endif
    syncTotal();

    @if(old('link_type') === 'personal' && old('new_consumer_name'))
        switchIndividualTab('new');
        document.getElementById('summaryIndividual').textContent = "{{ old('new_consumer_name') }}";
    @endif
});
</script>
@endpush
@endsection
