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

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.products.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
