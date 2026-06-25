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
    <div class="card p-6">
        <form method="POST" action="{{ route('merchant.invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="total_fee" class="form-label">Total Amount (AED)</label>
                <input type="number" step="0.01" name="total_fee" id="total_fee"
                    value="{{ old('total_fee', $invoice->total_fee) }}" min="0"
                    class="form-input @error('total_fee') border-red-400 @enderror">
                @error('total_fee')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-input @error('status') border-red-400 @enderror">
                    <option value="0" {{ old('status', $invoice->status->value) == 0 ? 'selected' : '' }}>Draft</option>
                    <option value="10" {{ old('status', $invoice->status->value) == 10 ? 'selected' : '' }}>Paid</option>
                    <option value="20" {{ old('status', $invoice->status->value) == 20 ? 'selected' : '' }}>Failed</option>
                </select>
                @error('status')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
