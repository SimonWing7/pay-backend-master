@extends('merchant.layout')

@section('title', 'Edit Payment Link')
@section('page-title', 'Edit Payment Link')
@section('page-subtitle', 'Update payment link details')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">

    {{-- Status notice --}}
    @php
        $statusValue = $invoice->status->value ?? $invoice->status;
        if ($statusValue == 10) {
            $statusLabel = 'Paid';
            $statusClass = 'background: rgba(16,185,129,0.1); color: #065f46; border: 1px solid rgba(16,185,129,0.3);';
            $statusIcon  = 'fa-check-circle';
        } elseif ($statusValue == 20) {
            $statusLabel = 'Failed';
            $statusClass = 'background: rgba(239,68,68,0.1); color: #991b1b; border: 1px solid rgba(239,68,68,0.3);';
            $statusIcon  = 'fa-times-circle';
        } else {
            $statusLabel = 'Pending';
            $statusClass = 'background: rgba(107,114,128,0.1); color: #374151; border: 1px solid rgba(107,114,128,0.25);';
            $statusIcon  = 'fa-clock';
        }
        $isPaid = ($statusValue == 10);
    @endphp

    <div class="card p-4 mb-4 flex items-center gap-3" style="{{ $statusClass }} border-radius: 10px;">
        <i class="fas {{ $statusIcon }}" style="font-size: 16px;"></i>
        <div>
            <p class="text-sm font-semibold">Status: {{ $statusLabel }}</p>
            @if($isPaid)
                <p class="text-xs" style="opacity: 0.75; margin-top: 2px;">This payment link has been paid. Amount cannot be changed.</p>
            @else
                <p class="text-xs" style="opacity: 0.75; margin-top: 2px;">Status is set automatically when a payer completes their payment — it cannot be changed manually.</p>
            @endif
        </div>
    </div>

    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="total_fee" class="form-label">Amount (AED)</label>
                @if($isPaid)
                    {{-- Paid invoices: show amount as read-only --}}
                    <div class="form-input" style="background: #f9fafb; color: #6b7280; cursor: not-allowed;">
                        {{ number_format($invoice->total_fee, 2) }}
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Amount cannot be changed after a payment has been made.</p>
                @else
                    <input type="number" step="0.01" name="total_fee" id="total_fee"
                        value="{{ old('total_fee', $invoice->total_fee) }}" min="0.01"
                        class="form-input @error('total_fee') border-red-400 @enderror">
                    @error('total_fee')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                @endif
            </div>

            <div class="mb-6">
                <label for="reference" class="form-label">Reference <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="text" name="reference" id="reference"
                    value="{{ old('reference', $invoice->reference) }}"
                    class="form-input @error('reference') border-red-400 @enderror"
                    placeholder="e.g. TERM1-2025"
                    maxlength="100">
                <p class="text-xs text-gray-400 mt-1">Your own code for reconciliation — appears in the payments CSV export.</p>
                @error('reference')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Customer Fields --}}
            @if(!$isPaid)
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Customer Fields</h3>
                <p class="text-xs text-gray-400 mb-4">Collected from customers before they pay. The three mandatory fields are always shown.</p>

                {{-- Mandatory fields (read-only display) --}}
                <div class="mb-5">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Mandatory Fields</p>
                    <div class="flex flex-wrap gap-2">
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#f0f1f5;color:#4b5563;border:1px solid #e2e5ef;">
                            <i class="fas fa-lock text-gray-400" style="font-size:10px;"></i> Name
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#f0f1f5;color:#4b5563;border:1px solid #e2e5ef;">
                            <i class="fas fa-lock text-gray-400" style="font-size:10px;"></i> Email
                        </span>
                        <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:600;background:#f0f1f5;color:#4b5563;border:1px solid #e2e5ef;">
                            <i class="fas fa-lock text-gray-400" style="font-size:10px;"></i> Mobile Number
                        </span>
                    </div>
                </div>

                {{-- Custom fields --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Custom Fields <span class="text-gray-400 font-normal normal-case">(up to 5)</span></p>
                    <div id="customFieldsList" class="space-y-3 mb-3">
                        {{-- On validation failure, restore from old(); otherwise pre-populate from saved invoice --}}
                        @if(old('custom_fields') !== null)
                            @foreach(old('custom_fields') as $idx => $cf)
                            <div class="custom-field-row flex items-center gap-3">
                                <input type="text"
                                    name="custom_fields[{{ $idx }}][label]"
                                    value="{{ $cf['label'] ?? '' }}"
                                    placeholder="Field label, e.g. Child name"
                                    class="form-input flex-1 text-sm"
                                    maxlength="100">
                                <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 cursor-pointer flex-shrink-0">
                                    <input type="hidden" name="custom_fields[{{ $idx }}][required]" value="0">
                                    <input type="checkbox"
                                        name="custom_fields[{{ $idx }}][required]"
                                        value="1"
                                        {{ !empty($cf['required']) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Required
                                </label>
                                <button type="button" onclick="removeCustomField(this)"
                                    class="text-gray-400 hover:text-red-500 transition-colors flex-shrink-0" title="Remove field">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        @elseif($invoice->custom_fields)
                            @foreach($invoice->custom_fields as $idx => $cf)
                            <div class="custom-field-row flex items-center gap-3">
                                <input type="text"
                                    name="custom_fields[{{ $idx }}][label]"
                                    value="{{ $cf['label'] ?? '' }}"
                                    placeholder="Field label, e.g. Child name"
                                    class="form-input flex-1 text-sm"
                                    maxlength="100">
                                <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 cursor-pointer flex-shrink-0">
                                    <input type="hidden" name="custom_fields[{{ $idx }}][required]" value="0">
                                    <input type="checkbox"
                                        name="custom_fields[{{ $idx }}][required]"
                                        value="1"
                                        {{ !empty($cf['required']) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Required
                                </label>
                                <button type="button" onclick="removeCustomField(this)"
                                    class="text-gray-400 hover:text-red-500 transition-colors flex-shrink-0" title="Remove field">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            @endforeach
                        @endif
                    </div>
                    <button type="button" id="addCustomFieldBtn" onclick="addCustomField()"
                        class="btn-secondary text-sm py-2">
                        <i class="fas fa-plus"></i> Add field
                    </button>
                </div>
            </div>
            @endif

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                @if(!$isPaid)
                    <button type="submit" class="btn-primary flex-1 justify-center">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                @endif
            </div>
        </form>
    </div>

    {{-- Info panel --}}
    <div class="card p-5 mt-4" style="background: linear-gradient(135deg, rgba(61,1,189,0.04), rgba(0,189,255,0.04));">
        <p class="text-xs font-semibold text-gray-600 mb-1"><i class="fas fa-info-circle text-blue-400 mr-1"></i> How payment status works</p>
        <p class="text-xs text-gray-500">Payment links start as <strong>Pending</strong>. When a payer completes their payment, the link is automatically marked as <strong>Paid</strong> and recorded in your payments history. This ensures accurate reconciliation.</p>
    </div>

</div>

@push('scripts')
<script>
// ---- Custom Fields ----
const MAX_CUSTOM_FIELDS = 5;

function getCustomFieldCount() {
    return document.querySelectorAll('#customFieldsList .custom-field-row').length;
}

function updateAddFieldBtn() {
    const btn = document.getElementById('addCustomFieldBtn');
    if (!btn) return;
    btn.disabled = getCustomFieldCount() >= MAX_CUSTOM_FIELDS;
    btn.style.opacity = btn.disabled ? '0.5' : '';
    btn.style.cursor = btn.disabled ? 'not-allowed' : '';
}

function addCustomField() {
    if (getCustomFieldCount() >= MAX_CUSTOM_FIELDS) return;
    const idx = Date.now(); // unique index
    const list = document.getElementById('customFieldsList');
    const row = document.createElement('div');
    row.className = 'custom-field-row flex items-center gap-3';
    row.innerHTML = `
        <input type="text"
            name="custom_fields[${idx}][label]"
            placeholder="Field label, e.g. Child name"
            class="form-input flex-1 text-sm"
            maxlength="100">
        <label class="flex items-center gap-1.5 text-xs font-medium text-gray-600 cursor-pointer flex-shrink-0">
            <input type="hidden" name="custom_fields[${idx}][required]" value="0">
            <input type="checkbox"
                name="custom_fields[${idx}][required]"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Required
        </label>
        <button type="button" onclick="removeCustomField(this)"
            class="text-gray-400 hover:text-red-500 transition-colors flex-shrink-0" title="Remove field">
            <i class="fas fa-times"></i>
        </button>
    `;
    list.appendChild(row);
    updateAddFieldBtn();
    row.querySelector('input[type="text"]').focus();
}

function removeCustomField(btn) {
    btn.closest('.custom-field-row').remove();
    updateAddFieldBtn();
}

// Initialise button state on page load
document.addEventListener('DOMContentLoaded', function() {
    updateAddFieldBtn();
});
// ---- End Custom Fields ----
</script>
@endpush

@endsection
