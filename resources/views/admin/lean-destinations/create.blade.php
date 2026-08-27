@extends('admin.layout')

@section('title', 'Create Lean Destination')
@section('page-title', 'Create Lean Destination')
@section('page-subtitle', 'Register a merchant or entity\'s bank account with Lean')

@section('content')
<div class="max-w-lg">

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl text-sm" style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;">
        <p class="font-semibold mb-2"><i class="fas fa-check-circle mr-1"></i>{{ session('success') }}</p>
        <div class="flex items-center gap-3">
            <code class="flex-1 px-3 py-2 rounded-lg text-xs font-mono break-all" style="background:#ffffff;border:1px solid #a7f3d0;color:#065f46;">{{ session('destination_id') }}</code>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl text-sm" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">
        <i class="fas fa-exclamation-circle mr-1"></i>{{ session('error') }}
    </div>
    @endif

    <div class="card p-6">
        <p class="text-xs text-gray-400 mb-5">Bank identifier isn't required for production destinations — Lean confirmed the IBAN alone tells them which institution to route to. This creates a real destination against Lean's live API.</p>

        <form method="POST" action="{{ route('admin.lean-destinations.store') }}">
            @csrf

            <div class="mb-5">
                <label for="display_name" class="form-label">Display Name <span class="text-red-400">*</span></label>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                    class="form-input @error('display_name') border-red-400 @enderror"
                    placeholder="e.g. Pinnakle Rugby — Dubai Account">
                @error('display_name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="name" class="form-label">Legal Account Holder Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="form-input @error('name') border-red-400 @enderror"
                    placeholder="Letters, numbers, and spaces only">
                <p class="text-xs text-gray-400 mt-1">Must match the bank account exactly — no punctuation (no "FZ-LLC", no periods).</p>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="address" class="form-label">Address <span class="text-red-400">*</span></label>
                <input type="text" name="address" id="address" value="{{ old('address') }}" required
                    class="form-input @error('address') border-red-400 @enderror">
                @error('address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="city" class="form-label">City <span class="text-red-400">*</span></label>
                <input type="text" name="city" id="city" value="{{ old('city') }}" required
                    class="form-input @error('city') border-red-400 @enderror"
                    placeholder="e.g. Dubai">
                @error('city')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="account_number" class="form-label">Account Number <span class="text-red-400">*</span></label>
                <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}" required
                    class="form-input @error('account_number') border-red-400 @enderror">
                @error('account_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="swift_code" class="form-label">SWIFT Code <span class="text-red-400">*</span></label>
                <input type="text" name="swift_code" id="swift_code" value="{{ old('swift_code') }}" required
                    class="form-input @error('swift_code') border-red-400 @enderror"
                    placeholder="e.g. EBILAEAD">
                @error('swift_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="iban" class="form-label">IBAN <span class="text-red-400">*</span></label>
                <input type="text" name="iban" id="iban" value="{{ old('iban') }}" required
                    class="form-input @error('iban') border-red-400 @enderror"
                    placeholder="AE070331234567890123456">
                @error('iban')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="bank_type" class="form-label">Bank Type</label>
                <select name="bank_type" id="bank_type" class="form-input @error('bank_type') border-red-400 @enderror">
                    <option value="SME" {{ old('bank_type', 'SME') === 'SME' ? 'selected' : '' }}>SME</option>
                    <option value="RETAIL" {{ old('bank_type') === 'RETAIL' ? 'selected' : '' }}>RETAIL</option>
                </select>
                @error('bank_type')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="trade_license_number" class="form-label">Trade License Number <span class="text-red-400">*</span></label>
                <input type="text" name="trade_license_number" id="trade_license_number" value="{{ old('trade_license_number') }}" required
                    class="form-input @error('trade_license_number') border-red-400 @enderror"
                    placeholder="e.g. XX-1234567">
                @error('trade_license_number')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                <i class="fas fa-university"></i> Create Destination
            </button>
        </form>
    </div>
</div>
@endsection
