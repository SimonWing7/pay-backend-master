@extends('admin.layout')

@section('title', 'Payment Details')
@section('page-title', 'Payment Details')
@section('page-subtitle', 'Full transaction record')

@section('topbar-actions')
    <a href="{{ route('admin.payments.index') }}" class="btn-secondary">
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
                <p class="text-sm text-gray-700">{{ $payment->created_at->format('d M Y, H:i:s') }}</p>
            </div>
            @if($payment->lean_payment_intent_id)
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Lean Payment Intent ID</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $payment->lean_payment_intent_id }}</p>
                <p class="text-xs text-gray-400 mt-1">Reference this when raising a case with Lean support — matches "Payment intent ID" in Lean's own dashboard.</p>
            </div>
            @endif
            @if(!empty($payment->lean_metadata['lean_customer_id']))
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Lean Customer ID</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $payment->lean_metadata['lean_customer_id'] }}</p>
                <p class="text-xs text-gray-400 mt-1">Matches "Customer ID" in Lean's dashboard.</p>
            </div>
            @endif
            @if(!empty($payment->lean_metadata['latest_webhook']['payload']['id']))
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Lean Payment ID</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $payment->lean_metadata['latest_webhook']['payload']['id'] }}</p>
                <p class="text-xs text-gray-400 mt-1">Matches "Payment ID" in Lean's dashboard — only available once a webhook has been received.</p>
            </div>
            @endif
            @if($payment->lean_metadata)
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Raw Lean Metadata</p>
                <details>
                    <summary class="text-xs cursor-pointer" style="color:#3d01bd;">View raw data (includes Lean's own Payment ID once a webhook has been received)</summary>
                    <pre class="text-xs font-mono text-gray-600 mt-2 p-3 rounded-lg overflow-x-auto" style="background:#f8f9fc;border:1px solid #eef0f5;white-space:pre-wrap;word-break:break-all;">{{ json_encode($payment->lean_metadata, JSON_PRETTY_PRINT) }}</pre>
                </details>
            </div>
            @endif
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
                <p class="text-xs text-gray-400 font-medium mb-1">Amount</p>
                <p class="text-2xl font-bold gradient-text">AED {{ number_format($payment->invoice->total_fee, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Merchant</p>
                <p class="text-sm text-gray-700">{{ $payment->invoice->merchant->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Individual</p>
                <p class="text-sm text-gray-700">{{ $payment->invoice->consumer->name ?? 'Open link (no individual)' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Payer Email</p>
                <p class="text-sm text-gray-700">{{ $payment->customer_email ?? $payment->invoice->consumer->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Payer Mobile</p>
                <p class="text-sm text-gray-700">{{ $payment->customer_mobile ?? $payment->invoice->consumer->mobile_number ?? '—' }}</p>
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
        </div>
    </div>
    @endif

    @if($payment->appUser)
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">App User (Payer)</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Name</p>
                <p class="text-sm text-gray-700">{{ $payment->appUser->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Email</p>
                <p class="text-sm text-gray-700">{{ $payment->appUser->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Device ID</p>
                <p class="text-xs font-mono text-gray-600 break-all">{{ $payment->appUser->device_id }}</p>
            </div>
            <a href="{{ route('admin.app_users.show', $payment->appUser->id) }}" class="btn-secondary text-sm">
                <i class="fas fa-user"></i> View App User
            </a>
        </div>
    </div>
    @endif

</div>
@endsection
