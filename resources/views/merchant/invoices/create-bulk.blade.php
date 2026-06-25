@extends('merchant.layout')

@section('title', 'Create Bulk Invoices')

@section('content')
<div class="px-4 py-5 sm:p-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Create Bulk Invoices</h1>

    <div class="bg-white shadow rounded-lg p-6">
        <form method="POST" action="{{ route('merchant.invoices.store-bulk') }}">
            @csrf

            <div class="mb-4">
                <label for="product_id" class="block text-gray-700 text-sm font-bold mb-2">Product *</label>
                <select name="product_id" id="product_id" required
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('product_id') border-red-500 @enderror">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} - AED {{ number_format($product->fee, 2) }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Consumers *</label>
                <div class="border rounded p-4 max-h-64 overflow-y-auto">
                    @foreach($consumers as $consumer)
                        <label class="flex items-center mb-2">
                            <input type="checkbox" name="consumer_ids[]" value="{{ $consumer->id }}" 
                                class="form-checkbox h-5 w-5 text-blue-600">
                            <span class="ml-2 text-gray-700">
                                {{ $consumer->name }}
                                @if($consumer->email)
                                    <span class="text-gray-500">({{ $consumer->email }})</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('consumer_ids')
                    <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
                <p class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    This will create a separate invoice for each selected consumer with the selected product.
                </p>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('merchant.invoices.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Create Bulk Invoices
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

