@extends('admin.layout')

@section('title', 'Merchants')
@section('page-title', 'Merchants')
@section('page-subtitle', 'Manage all merchant accounts')

@section('topbar-actions')
    <a href="{{ route('admin.merchants.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Merchant
    </a>
@endsection

@section('content')

<x-filter-sort
    :route="route('admin.merchants.index')"
    :filters="[
        'search' => true,
        'is_active' => true,
        'sort_options' => ['name' => 'Name', 'email' => 'Email', 'created_at' => 'Created At', 'updated_at' => 'Updated At']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Merchant</th>
                <th class="text-left">Email</th>
                <th class="text-left">IBAN</th>
                <th class="text-left">Status</th>
                <th class="text-left">Joined</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($merchants as $merchant)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('admin.merchants.show', $merchant->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:50%;flex-shrink:0;">
                            {{ strtoupper(substr($merchant->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-gray-800">{{ $merchant->name }}</div>
                            @if($merchant->merchant_trading_name && $merchant->merchant_trading_name !== $merchant->name)
                                <div class="text-xs text-gray-400">Trading: {{ $merchant->merchant_trading_name }}</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">{{ $merchant->email }}</td>
                <td>
                    @if($merchant->iban)
                        <span class="text-xs font-mono text-gray-500">{{ substr($merchant->iban, 0, 10) }}…</span>
                    @else
                        <span class="badge-warning">Not set</span>
                    @endif
                </td>
                <td>
                    @if($merchant->is_active)
                        <span class="badge-success">Active</span>
                    @else
                        <span class="badge-danger">Inactive</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $merchant->created_at->format('d M Y') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex items-center justify-end gap-3">
                        <form method="POST" action="{{ route('admin.merchants.toggle-active', $merchant->id) }}" class="inline">
                            @csrf
                            <button type="submit" onclick="event.stopPropagation();"
                                class="{{ $merchant->is_active ? 'text-gray-400 hover:text-amber-500' : 'text-gray-400 hover:text-green-600' }} transition-colors text-sm"
                                title="{{ $merchant->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="fas fa-{{ $merchant->is_active ? 'ban' : 'check-circle' }}"></i>
                            </button>
                        </form>
                        <a href="{{ route('admin.merchants.show', $merchant->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        <a href="{{ route('admin.merchants.edit', $merchant->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.merchants.delete', $merchant->id) }}" class="inline"
                            onsubmit="event.stopPropagation(); return confirm('Delete this merchant permanently?');">
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
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-store"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No merchants found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($merchants->hasPages())
<div class="mt-4">{{ $merchants->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $merchants->firstItem() ?? 0 }}–{{ $merchants->lastItem() ?? 0 }} of {{ $merchants->total() }} results
</div>
@endsection
