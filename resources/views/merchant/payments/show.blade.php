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

@if($payment->isNeedsReview())
<div class="card p-6 mb-6" style="border-color:#fcd34d;">
    <h3 class="text-sm font-semibold text-gray-800 mb-1"><i class="fas fa-exclamation-triangle text-amber-500"></i> Awaiting bank confirmation</h3>
    <p class="text-sm text-gray-600 mb-4">
        The customer's bank accepted this payment but hasn't confirmed it, and the payment provider won't send a final status.
        Please check your bank account for a credit of <strong>AED {{ number_format($payment->invoice->total_fee ?? 0, 2) }}</strong>
        @if(!empty($payment->lean_metadata['latest_webhook']['payload']['bank_transaction_reference']))
            (bank reference <span class="font-mono text-xs">{{ $payment->lean_metadata['latest_webhook']['payload']['bank_transaction_reference'] }}</span>)
        @endif
        around {{ $payment->created_at->format('d M Y') }}.
    </p>
    @if(auth('merchants')->user()->allow_payment_reconciliation)
    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('merchant.payments.confirm', $payment->id) }}"
            onsubmit="return confirm('The money is in your bank account? This marks the payment as paid, notifies your store, and emails the customer a receipt.');">
            @csrf
            <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Confirm received</button>
        </form>
        <form method="POST" action="{{ route('merchant.payments.reject', $payment->id) }}"
            onsubmit="return confirm('No matching credit found? This marks the payment as not received.');">
            @csrf
            <button type="submit" class="btn-secondary" style="color:#dc2626;"><i class="fas fa-times"></i> Not received</button>
        </form>
    </div>
    @else
    <p class="text-sm text-gray-500">Edfundo is reviewing this payment with you — contact us if you've confirmed the credit.</p>
    @endif
</div>
@endif

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
                @if($payment->isNeedsReview())
                    <span class="badge-warning"><i class="fas fa-exclamation-triangle"></i> Awaiting bank confirmation</span>
                @elseif($payment->status->value === 10)
                    <span class="badge-success">{{ $payment->status->label() }}</span>
                @elseif($payment->status->value === 20)
                    <span class="badge-danger">{{ $payment->status->label() }}</span>
                @elseif($payment->status->value === 30)
                    <span class="badge-muted">{{ $payment->status->label() }}</span>
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
