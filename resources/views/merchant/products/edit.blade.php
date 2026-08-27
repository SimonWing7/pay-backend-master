@extends('merchant.layout')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')
@section('page-subtitle', 'Update product details')

@section('topbar-actions')
    <a href="{{ route('merchant.products.show', $product->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.products.update', $product->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="name" class="form-label">Product Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                    class="form-input @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="description" class="form-label">Description <span class="text-red-400">*</span></label>
                <textarea name="description" id="description" rows="3" required
                    class="form-input @error('description') border-red-400 @enderror">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="fee" class="form-label">Fee (AED) <span class="text-red-400">*</span></label>
                <input type="number" step="0.01" name="fee" id="fee" value="{{ old('fee', $product->fee) }}" required min="0"
                    class="form-input @error('fee') border-red-400 @enderror">
                @error('fee')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            @if($entities->count() > 0)
            <div class="mb-5">
                <label for="merchant_entity_id" class="form-label">Entity <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="merchant_entity_id" id="merchant_entity_id" class="form-input @error('merchant_entity_id') border-red-400 @enderror">
                    <option value="">— None —</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}" {{ old('merchant_entity_id', $product->merchant_entity_id) == $entity->id ? 'selected' : '' }}>{{ $entity->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Which company this product's payments should route to.</p>
                @error('merchant_entity_id')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div class="mb-6">
                <label for="state" class="form-label">Status</label>
                <select name="state" id="state" class="form-input @error('state') border-red-400 @enderror">
                    <option value="active" {{ old('state', $product->state) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="archived" {{ old('state', $product->state) === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
                @error('state')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Custom Fields <span class="text-gray-400 font-normal normal-case">(up to 5)</span></p>
                <p class="text-xs text-gray-400 mb-3">Extra info collected from the payer, e.g. "Child's Name" — shown to anyone who pays via this product's link.</p>
                <div id="customFieldsList" class="space-y-3 mb-3">
                    @php $existingCustomFields = old('custom_fields', $product->custom_fields ?? []); @endphp
                    @foreach($existingCustomFields as $idx => $cf)
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
                </div>
                <button type="button" id="addCustomFieldBtn" onclick="addCustomField()"
                    class="btn-secondary text-sm py-2">
                    <i class="fas fa-plus"></i> Add field
                </button>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.products.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const MAX_CUSTOM_FIELDS = 5;

function getCustomFieldCount() {
    return document.querySelectorAll('#customFieldsList .custom-field-row').length;
}

function updateAddFieldBtn() {
    const btn = document.getElementById('addCustomFieldBtn');
    btn.disabled = getCustomFieldCount() >= MAX_CUSTOM_FIELDS;
    btn.style.opacity = btn.disabled ? '0.5' : '';
    btn.style.cursor = btn.disabled ? 'not-allowed' : '';
}

function addCustomField() {
    if (getCustomFieldCount() >= MAX_CUSTOM_FIELDS) return;
    const idx = Date.now();
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

document.addEventListener('DOMContentLoaded', function() {
    updateAddFieldBtn();
});
</script>
@endpush
@endsection
