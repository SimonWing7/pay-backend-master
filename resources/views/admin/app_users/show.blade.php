@extends('admin.layout')

@section('title', 'App User Details')
@section('page-title', 'App User Details')
@section('page-subtitle', 'View user profile and payment history')

@section('topbar-actions')
    <a href="{{ route('admin.app_users.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">User Profile</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">User ID</p>
                <p class="text-sm font-bold text-gray-800">#{{ $appUser->id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">UUID</p>
                <p class="text-xs font-mono text-gray-600">{{ $appUser->uuid }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Name</p>
                <p class="text-sm text-gray-700">{{ $appUser->name ?? 'Not set' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Email</p>
                <p class="text-sm text-gray-700">{{ $appUser->email ?? 'Not set' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Device ID</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $appUser->device_id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">First Seen</p>
                <p class="text-sm text-gray-700">{{ $appUser->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Last Updated</p>
                <p class="text-sm text-gray-700">{{ $appUser->updated_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="stat-card">
            <div class="flex items-center gap-4">
                <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                <div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Payments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $appUser->payments->count() }}</p>
                </div>
            </div>
        </div>

        @if($appUser->meta && count($appUser->meta) > 0)
        <div class="card p-6">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Request Metadata</h3>
            <div class="space-y-3">
                @if(isset($appUser->meta['ip']))
                <div>
                    <p class="text-xs text-gray-400 font-medium mb-0.5">IP Address</p>
                    <p class="text-sm text-gray-700">{{ $appUser->meta['ip'] }}</p>
                </div>
                @endif
                @if(isset($appUser->meta['last_login_ip']))
                <div>
                    <p class="text-xs text-gray-400 font-medium mb-0.5">Last Login IP</p>
                    <p class="text-sm text-gray-700">{{ $appUser->meta['last_login_ip'] }}</p>
                </div>
                @endif
                @if(isset($appUser->meta['user_agent']))
                <div>
                    <p class="text-xs text-gray-400 font-medium mb-0.5">User Agent</p>
                    <p class="text-xs text-gray-600 break-all">{{ $appUser->meta['user_agent'] }}</p>
                </div>
                @endif
                @if(isset($appUser->meta['last_login_at']))
                <div>
                    <p class="text-xs text-gray-400 font-medium mb-0.5">Last Login</p>
                    <p class="text-sm text-gray-700">{{ $appUser->meta['last_login_at'] }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>

{{-- Payments Table --}}
@if($appUser->payments->count() > 0)
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Payment History</h3>
    </div>
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment ID</th>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Status</th>
                <th class="text-left">Date</th>
                <th class="text-right">View</th>
            </tr>
        </thead>
        <tbody>
            @foreach($appUser->payments as $payment)
            <tr>
                <td class="font-medium text-sm">#{{ $payment->id }}</td>
                <td class="text-xs font-mono text-gray-400">{{ substr($payment->invoice->uuid ?? '', 0, 14) }}…</td>
                <td class="font-semibold text-sm">AED {{ number_format($payment->invoice->total_fee ?? 0, 2) }}</td>
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
                <td class="text-right">
                    <a href="{{ route('admin.payments.show', $payment->id) }}" class="text-gray-400 hover:text-blue-600">
                        <i class="fas fa-eye text-sm"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="card p-12 text-center">
    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
        <i class="fas fa-credit-card"></i>
    </div>
    <p class="text-gray-500 font-medium">No payments yet</p>
</div>
@endif
@endsection
