@extends('merchant.layout')

@section('title', 'Edit Group')
@section('page-title', 'Edit Group')
@section('page-subtitle', 'Update group details and members')

@section('topbar-actions')
    <a href="{{ route('merchant.groups.show', $group->id) }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.groups.update', $group->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="name" class="form-label">Group Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required
                    class="form-input @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="form-label">Colour <span class="text-red-400">*</span></label>
                <div class="flex items-center gap-3">
                    <input type="color" name="color" id="color" value="{{ old('color', $group->color) }}" required
                        class="h-10 w-14 rounded-lg border border-gray-200 cursor-pointer">
                    <input type="text" name="color_text" id="color_text" value="{{ old('color', $group->color) }}"
                        pattern="^#[0-9A-Fa-f]{6}$" placeholder="#3d01bd"
                        class="form-input" style="max-width:130px;"
                        onchange="document.getElementById('color').value = this.value; updatePreview(this.value);">
                    <div class="h-10 w-10 rounded-lg border border-gray-200 transition-colors" id="color_preview"
                        style="background-color: {{ old('color', $group->color) }};"></div>
                </div>
                @error('color')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="consumer_ids" class="form-label">Individuals</label>
                <select name="consumer_ids[]" id="consumer_ids" multiple
                    class="form-input @error('consumer_ids') border-red-400 @enderror" style="height:150px;">
                    @foreach($consumers as $consumer)
                        <option value="{{ $consumer->id }}" {{ in_array($consumer->id, old('consumer_ids', $group->consumers->pluck('id')->toArray())) ? 'selected' : '' }}>
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
                    <i class="fas fa-save"></i> Save Changes
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
