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
    <div class="card p-6">
        <form method="POST" action="{{ route('admin.merchants.update', $merchant->id) }}">
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
                    class="form-input @error('merchant_trading_name') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">If not set, the merchant name is used</p>
                @error('merchant_trading_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="email" class="form-label">Email Address <span class="text-red-400">*</span></label>
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

            <div class="mb-5">
                <label for="iban" class="form-label">IBAN</label>
                <input type="text" name="iban" id="iban" value="{{ old('iban', $merchant->iban) }}" placeholder="AE070331234567890123456"
                    class="form-input @error('iban') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Required for NymCard payment processing</p>
                @error('iban')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="category_code" class="form-label">Merchant Category Code (MCC)</label>
                <input type="text" name="category_code" id="category_code" value="{{ old('category_code', $merchant->category_code) }}" placeholder="5411"
                    class="form-input @error('category_code') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Default: 5411</p>
                @error('category_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="sic_code" class="form-label">SIC Code</label>
                <input type="text" name="sic_code" id="sic_code" value="{{ old('sic_code', $merchant->sic_code) }}" placeholder="5411"
                    class="form-input @error('sic_code') border-red-400 @enderror">
                @error('sic_code')
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
                <a href="{{ route('admin.merchants.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
