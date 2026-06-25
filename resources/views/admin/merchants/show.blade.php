@extends('admin.layout')

@section('title', 'Merchant Details')
@section('page-title', 'Merchant Details')
@section('page-subtitle', 'View merchant account information')

@section('topbar-actions')
    <form method="POST" action="{{ route('admin.merchants.toggle-active', $merchant->id) }}" class="inline">
        @csrf
        <button type="submit" class="btn-secondary">
            <i class="fas fa-{{ $merchant->is_active ? 'ban' : 'check-circle' }}"></i>
            {{ $merchant->is_active ? 'Deactivate' : 'Activate' }}
        </button>
    </form>
    <a href="{{ route('admin.merchants.edit', $merchant->id) }}" class="btn-secondary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="{{ route('admin.merchants.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Account Details</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Name</p>
                <p class="text-base font-bold text-gray-800">{{ $merchant->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Trading Name</p>
                <p class="text-sm text-gray-700">{{ $merchant->merchant_trading_name ?? $merchant->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Email</p>
                <p class="text-sm text-gray-700">{{ $merchant->email }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Status</p>
                @if($merchant->is_active)
                    <span class="badge-success">Active</span>
                @else
                    <span class="badge-danger">Inactive</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Joined</p>
                <p class="text-sm text-gray-700">{{ $merchant->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Last Updated</p>
                <p class="text-sm text-gray-700">{{ $merchant->updated_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment Configuration</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">IBAN</p>
                @if($merchant->iban)
                    <p class="text-sm font-mono text-gray-700">{{ $merchant->iban }}</p>
                @else
                    <span class="badge-warning">Not configured</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Merchant Category Code (MCC)</p>
                <p class="text-sm text-gray-700">{{ $merchant->category_code ?? 'Default (5411)' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">SIC Code</p>
                <p class="text-sm text-gray-700">{{ $merchant->sic_code ?? 'Default (5411)' }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
