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
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    {{-- Account Details --}}
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
                <p class="text-xs text-gray-400 font-medium mb-1">Login Email</p>
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

    {{-- Payment Configuration --}}
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
                <p class="text-xs text-gray-400 font-medium mb-1">Lean Payment Destination ID</p>
                @if($merchant->lean_destination_id)
                    <p class="text-sm font-mono text-gray-700">{{ $merchant->lean_destination_id }}</p>
                @else
                    <span class="badge-warning">Not configured — payments fall back to the platform default</span>
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

    {{-- Business Profile --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Business Profile</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Support Email</p>
                @if($merchant->support_email)
                    <a href="mailto:{{ $merchant->support_email }}" class="text-sm text-indigo-600 hover:underline">{{ $merchant->support_email }}</a>
                @else
                    <p class="text-sm text-gray-400 italic">Not set</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Support Phone</p>
                <p class="text-sm text-gray-700">{{ $merchant->support_phone ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Website</p>
                @if($merchant->website)
                    <a href="{{ $merchant->website }}" target="_blank" rel="noopener" class="text-sm text-indigo-600 hover:underline">{{ $merchant->website }}</a>
                @else
                    <p class="text-sm text-gray-400 italic">Not set</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Webhook Configuration --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Webhook Configuration</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Webhook URL</p>
                @if($merchant->webhook_url)
                    <p class="text-sm font-mono text-gray-700 break-all">{{ $merchant->webhook_url }}</p>
                @else
                    <p class="text-sm text-gray-400 italic">Not configured</p>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Webhook Secret</p>
                @if($merchant->webhook_secret)
                    <span class="badge-success"><i class="fas fa-check-circle mr-1"></i>Generated</span>
                @else
                    <span class="badge-warning">Not generated</span>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- API Keys --}}
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">API Keys</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ $apiKeys->count() }} active {{ Str::plural('key', $apiKeys->count()) }}</p>
        </div>
        <a href="{{ route('admin.merchants.edit', $merchant->id) }}" class="text-xs text-indigo-600 hover:underline font-medium">
            Manage via edit page
        </a>
    </div>

    @if($apiKeys->isEmpty())
    <div class="px-6 py-10 text-center">
        <div class="stat-icon mx-auto mb-3" style="width:40px;height:40px;font-size:16px;">
            <i class="fas fa-key"></i>
        </div>
        <p class="text-sm text-gray-500">No API keys created yet</p>
        <p class="text-xs text-gray-400 mt-1">The merchant can create keys from Settings → API Keys in their dashboard</p>
    </div>
    @else
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Key Name</th>
                <th class="text-left">Prefix</th>
                <th class="text-left">Last Used</th>
                <th class="text-left">Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($apiKeys as $key)
            <tr>
                <td class="text-sm font-medium text-gray-800">{{ $key->name }}</td>
                <td class="text-xs font-mono text-gray-500">{{ $key->key_prefix }}</td>
                <td class="text-sm text-gray-600">
                    {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}
                </td>
                <td class="text-sm text-gray-600">{{ $key->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Recent Payments --}}
<div class="card overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-700">Recent Payments</h3>
            <p class="text-xs text-gray-400 mt-0.5">{{ $recentPayments->total() }} total {{ Str::plural('payment', $recentPayments->total()) }} for this merchant</p>
        </div>
        <a href="{{ route('admin.payments.index', ['merchant_id' => $merchant->id]) }}" class="text-xs text-indigo-600 hover:underline font-medium">
            View All Payments
        </a>
    </div>

    @if($recentPayments->isEmpty())
    <div class="px-6 py-10 text-center">
        <div class="stat-icon mx-auto mb-3" style="width:40px;height:40px;font-size:16px;">
            <i class="fas fa-credit-card"></i>
        </div>
        <p class="text-sm text-gray-500">No payments yet</p>
    </div>
    @else
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment</th>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Status</th>
                <th class="text-left">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentPayments as $payment)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('admin.payments.show', $payment->id) }}'">
                <td class="text-sm font-semibold text-gray-800">#{{ $payment->id }}</td>
                <td class="text-xs font-mono text-gray-400">{{ substr($payment->invoice->uuid ?? '', 0, 12) }}…</td>
                <td>
                    @if($payment->status->value === 10)
                        <span class="badge-success">{{ $payment->status->label() }}</span>
                    @elseif($payment->status->value === 20)
                        <span class="badge-danger">{{ $payment->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $payment->status->label() }}</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $payment->created_at->format('d M Y, H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

@endsection
