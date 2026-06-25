@extends('merchant.layout')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('page-subtitle', 'Transaction information')

@section('topbar-actions')
    <a href="{{ route('merchant.payments.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Transaction</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Payment ID</p>
                <p class="text-sm font-bold text-gray-800">#{{ $payment->id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Status</p>
                @if($payment->status->value === 10)
                    <span class="badge-success">{{ $payment->status->label() }}</span>
                @elseif($payment->status->value === 20)
                    <span class="badge-danger">{{ $payment->status->label() }}</span>
                @else
                    <span class="badge-warning">{{ $payment->status->label() }}</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Token</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $payment->token }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Date</p>
                <p class="text-sm text-gray-800">{{ $payment->created_at->format('d M Y, H:i:s') }}</p>
            </div>
        </div>
    </div>

    @if($payment->invoice)
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment Link</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">UUID</p>
                <p class="text-xs font-mono text-gray-600">{{ $payment->invoice->uuid }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Total Amount</p>
                <p class="text-2xl font-bold gradient-text">AED {{ number_format($payment->invoice->total_fee, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Individual</p>
                <p class="text-sm text-gray-800">{{ $payment->invoice->consumer->name ?? 'Open link (no individual)' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Link Status</p>
                @if($payment->invoice->status->value === 10)
                    <span class="badge-success">Paid</span>
                @elseif($payment->invoice->status->value === 20)
                    <span class="badge-danger">Failed</span>
                @else
                    <span class="badge-warning">{{ $payment->invoice->status->label() }}</span>
                @endif
            </div>
            <a href="{{ route('merchant.invoices.show', $payment->invoice->id) }}" class="btn-secondary text-sm">
                <i class="fas fa-link"></i> View Payment Link
            </a>
        </div>
    </div>
    @endif

    @if($payment->appUser)
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payer (App User)</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Name</p>
                <p class="text-sm text-gray-800">{{ $payment->appUser->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Email</p>
                <p class="text-sm text-gray-800">{{ $payment->appUser->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Device ID</p>
                <p class="text-xs font-mono text-gray-600">{{ $payment->appUser->device_id }}</p>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
