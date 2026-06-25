@extends('merchant.layout')

@section('title', 'New Group')
@section('page-title', 'New Group')
@section('page-subtitle', 'Create a group to organise individuals')

@section('topbar-actions')
    <a href="{{ route('merchant.groups.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.groups.store') }}">
            @csrf

            <div class="mb-5">
                <label for="name" class="form-label">Group Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="form-input @error('name') border-red-400 @enderror"
                    placeholder="e.g. Year 5 — Term 1">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="form-label">Colour <span class="text-red-400">*</span></label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" id="color" value="{{ old('color', '#3d01bd') }}" required
                        class="h-10 w-14 rounded-lg border border-gray-200 cursor-pointer">
                    <input type="text" name="color_text" id="color_text" value="{{ old('color', '#3d01bd') }}"
                        pattern="^#[0-9A-Fa-f]{6}$" placeholder="#3d01bd"
                        class="form-input" style="max-width:130px;"
                        onchange="document.getElementById('color').value = this.value; updatePreview(this.value);">
                    <div class="h-10 w-10 rounded-lg border border-gray-200 transition-colors" id="color_preview"
                        style="background-color: {{ old('color', '#3d01bd') }};"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Pick a colour to identify this group visually</p>
                @error('color')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="consumer_ids" class="form-label">Individuals <span class="text-gray-400 font-normal">(optional)</span></label>
                <select name="consumer_ids[]" id="consumer_ids" multiple
                    class="form-input @error('consumer_ids') border-red-400 @enderror" style="height:150px;">
                    @foreach($consumers as $consumer)
                        <option value="{{ $consumer->id }}" {{ in_array($consumer->id, old('consumer_ids', [])) ? 'selected' : '' }}>
                            {{ $consumer->name }}@if($consumer->email) ({{ $consumer->email }})@endif
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Hold Ctrl / Cmd to select multiple</p>
                @error('consumer_ids')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.groups.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-layer-group"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updatePreview(color) {
    document.getElementById('color_preview').style.backgroundColor = color;
}
document.addEventListener('DOMContentLoaded', function() {
    const colorInput = document.getElementById('color');
    const colorText = document.getElementById('color_text');
    colorInput.addEventListener('input', function() {
        colorText.value = this.value;
        updatePreview(this.value);
    });
    colorText.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            colorInput.value = this.value;
            updatePreview(this.value);
        }
    });
});
</script>
@endpush
@endsection
