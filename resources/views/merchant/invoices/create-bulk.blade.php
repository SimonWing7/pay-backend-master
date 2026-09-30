@extends('merchant.layout')

@section('title', 'Create Bulk Invoices')
@section('page-title', 'Create Bulk Invoices')
@section('page-subtitle', 'Invoice many parents/individuals at once')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

{{-- Step 1: upload parents/consumers via CSV --}}
<div class="card p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-1">Step 1: Upload Parents (CSV)</h3>
    <p class="text-xs text-gray-400 mb-4">Columns: Parent Name, Student/Player Name, Parent Email, Parent Mobile. Re-uploading the same file later updates existing individuals instead of duplicating them.</p>
    <form method="POST" action="{{ route('merchant.consumers.import') }}" enctype="multipart/form-data" class="flex items-center gap-3">
        @csrf
        <input type="hidden" name="redirect_to" value="invoices.create-bulk">
        <input type="file" name="file" accept=".csv,.txt" required class="form-input text-sm flex-1">
        <button type="submit" class="btn-secondary flex-shrink-0">
            <i class="fas fa-upload"></i> Upload CSV
        </button>
    </form>
    @error('file')
        <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
    @enderror
</div>

{{-- Step 2: choose product + select consumers --}}
<div class="card p-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-4">Step 2: Choose Product &amp; Select Parents</h3>
    <form method="POST" action="{{ route('merchant.invoices.store-bulk') }}">
        @csrf

        <div class="mb-5">
            <label for="product_id" class="form-label">Product *</label>
            <select name="product_id" id="product_id" required
                class="form-input @error('product_id') border-red-400 @enderror">
                <option value="">Select Product</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }} - AED {{ number_format($product->fee, 2) }}
                    </option>
                @endforeach
            </select>
            @error('product_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label class="form-label">Individuals *</label>
            <div class="border border-gray-200 rounded-lg p-4 max-h-72 overflow-y-auto">
                @forelse($consumers as $consumer)
                    <label class="flex items-center mb-2 last:mb-0">
                        <input type="checkbox" name="consumer_ids[]" value="{{ $consumer->id }}"
                            class="form-checkbox h-4 w-4 text-blue-600 rounded">
                        <span class="ml-2 text-sm text-gray-700">
                            {{ $consumer->name }}
                            @if($consumer->student_name)
                                <span class="text-gray-400">— {{ $consumer->student_name }}</span>
                            @endif
                            @if($consumer->email)
                                <span class="text-gray-400">({{ $consumer->email }})</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="text-sm text-gray-400">No individuals yet — upload a CSV above or <a href="{{ route('merchant.consumers.create') }}" class="text-blue-600">add one manually</a>.</p>
                @endforelse
            </div>
            @error('consumer_ids')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label class="flex items-center">
                <input type="checkbox" name="send_emails" value="1" checked
                    class="form-checkbox h-4 w-4 text-blue-600 rounded">
                <span class="ml-2 text-sm text-gray-700">Email each parent their payment link now</span>
            </label>
        </div>

        @error('error')
            <div class="alert-error mb-5">{{ $message }}</div>
        @enderror

        <div class="flex items-center justify-between">
            <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-file-invoice"></i> Create Bulk Invoices
            </button>
        </div>
    </form>
</div>
@endsection
