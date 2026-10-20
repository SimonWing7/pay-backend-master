{{-- Expects: $name, $label; optional: $type, $hint, $placeholder, $required, $answers --}}
@php
    $type = $type ?? 'text';
    $value = old($name, $answers[$name] ?? '');
@endphp
<div class="mb-5">
    <label for="{{ $name }}" class="form-label">{{ $label }}@if(!empty($required)) <span style="color:#f87171;">*</span>@else <span style="color:#9ca3af;font-weight:400;">(optional)</span>@endif</label>
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}"
        class="form-input" style="{{ $errors->has($name) ? 'border-color:#f87171;' : '' }}"
        @if(!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
        @if(!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif>
    @if(!empty($hint))<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="field-error">{{ $message }}</p>@enderror
</div>
