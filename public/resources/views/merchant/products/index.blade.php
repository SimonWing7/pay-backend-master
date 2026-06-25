@extends('merchant.layout')

@section('title', 'Products')
@section('page-title', 'Products')
@section('page-subtitle', 'Manage your product catalogue')

@section('topbar-actions')
    <a href="{{ route('merchant.products.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Product
    </a>
@endsection

@section('content')

<x-filter-sort
    :route="route('merchant.products.index')"
    :filters="[
        'search' => true,
        'min_fee' => true,
        'max_fee' => true,
        'status' => true,
        'status_options' => ['active' => 'Active', 'archived' => 'Archived', '' => 'All'],
        'sort_options' => ['name' => 'Name', 'fee' => 'Fee', 'created_at' => 'Created At', 'updated_at' => 'Updated At']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Product</th>
                <th class="text-left">Description</th>
                <th class="text-left">Fee</th>
                <th class="text-left">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.products.show', $product->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:8px;flex-shrink:0;">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div class="font-semibold text-sm text-gray-800">{{ $product->name }}</div>
                    </div>
                </td>
                <td class="text-sm text-gray-500">{{ Str::limit($product->description, 50) }}</td>
                <td class="font-semibold text-sm text-gray-800">AED {{ number_format($product->fee, 2) }}</td>
                <td>
                    @if($product->state === 'active')
                        <span class="badge-success">Active</span>
                    @else
                        <span class="badge-info">Archived</span>
                    @endif
                </td>
                <td onclick="event.stopPropagation();">
                    <div class="flex items-center justify-end gap-3">
                        <button
                            onclick="event.preventDefault(); event.stopPropagation(); copyProductLink('{{ route('public.product', $product->uuid) }}', '{{ $product->id }}')"
                            class="text-gray-400 hover:text-purple-600 transition-colors"
                            title="Copy Product Link"
                            id="copyBtn{{ $product->id }}">
                            <i class="fas fa-link text-sm"></i>
                        </button>
                        <form method="POST" action="{{ route('merchant.products.toggle-state', $product->id) }}" class="inline"
                            onsubmit="event.stopPropagation(); return confirm('{{ $product->state === 'active' ? 'Archive' : 'Activate' }} this product?');">
                            @csrf
                            <button type="submit"
                                class="{{ $product->state === 'active' ? 'text-gray-400 hover:text-amber-500' : 'text-gray-400 hover:text-green-600' }} transition-colors"
                                title="{{ $product->state === 'active' ? 'Archive' : 'Activate' }}">
                                <i class="fas {{ $product->state === 'active' ? 'fa-archive' : 'fa-check-circle' }} text-sm"></i>
                            </button>
                        </form>
                        <a href="{{ route('merchant.products.show', $product->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        <a href="{{ route('merchant.products.edit', $product->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('merchant.products.delete', $product->id) }}" class="inline"
                            onsubmit="event.stopPropagation(); return confirm('Delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-tag"></i>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">No products yet</p>
                    <p class="text-gray-400 text-sm mb-4">Create products to quickly fill payment links</p>
                    <a href="{{ route('merchant.products.create') }}" class="btn-primary">
                        <i class="fas fa-plus"></i> New Product
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($products->hasPages())
<div class="mt-4">{{ $products->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} results
</div>

@push('scripts')
<script>
function copyProductLink(link, productId) {
    const button = document.getElementById('copyBtn' + productId);
    navigator.clipboard.writeText(link).then(function() {
        button.innerHTML = '<i class="fas fa-check text-green-500 text-sm"></i>';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-link text-sm"></i>'; }, 2000);
    }).catch(function() {
        const ta = document.createElement('textarea');
        ta.value = link;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        button.innerHTML = '<i class="fas fa-check text-green-500 text-sm"></i>';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-link text-sm"></i>'; }, 2000);
    });
}
</script>
@endpush
@endsection
