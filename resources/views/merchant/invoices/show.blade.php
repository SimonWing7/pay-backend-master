@extends('merchant.layout')

@section('title', 'Payment Link Details')
@section('page-title', 'Payment Link Details')
@section('page-subtitle', 'View and share this payment link')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.edit', $invoice->id) }}" class="btn-secondary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

{{-- Share Link Banner --}}
<div class="card p-6 mb-6">
    <p class="form-label mb-2">Payment Link</p>
    <div class="flex items-center gap-3">
        <input type="text"
            id="paymentLink"
            value="{{ route('public.invoice.show', $invoice->uuid) }}"
            readonly
            class="form-input bg-gray-50 font-mono text-sm flex-1">
        <button onclick="copyPaymentLink('{{ route('public.invoice.show', $invoice->uuid) }}')"
            class="btn-primary flex-shrink-0" id="copyButton">
            <i class="fas fa-copy"></i> Copy
        </button>
    </div>
    <p class="text-xs text-gray-400 mt-2">Share this link with your customer to collect payment</p>
</div>

{{-- Info Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Payment Link Info</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Link ID</p>
                <p class="text-sm font-semibold text-gray-800">#{{ $invoice->id }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">UUID</p>
                <p class="text-sm font-mono text-gray-600">{{ $invoice->uuid }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Total Amount</p>
                <p class="text-2xl font-bold gradient-text">AED {{ number_format($invoice->total_fee, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Status</p>
                @if($invoice->status->value === 10)
                    <span class="badge-success">Paid</span>
                @elseif($invoice->status->value === 20)
                    <span class="badge-danger">Failed</span>
                @else
                    <span class="badge-warning">{{ $invoice->status->label() }}</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Created</p>
                <p class="text-sm text-gray-600">{{ $invoice->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Individual</h3>
        @if($invoice->consumer)
        <div class="flex items-center gap-3 mb-4">
            <div class="stat-icon" style="width:44px;height:44px;font-size:16px;flex-shrink:0;">
                {{ strtoupper(substr($invoice->consumer->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-800">{{ $invoice->consumer->name }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->consumer->email ?? '—' }}</p>
                <p class="text-sm text-gray-500">{{ $invoice->consumer->mobile_number ?? '' }}</p>
            </div>
        </div>
        <a href="{{ route('merchant.consumers.show', $invoice->consumer->id) }}" class="btn-secondary text-sm">
            <i class="fas fa-user"></i> View Individual
        </a>
        @else
        <div class="text-center py-6">
            <div class="stat-icon mx-auto mb-3" style="width:40px;height:40px;font-size:16px;">
                <i class="fas fa-globe"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Open Link</p>
            <p class="text-xs text-gray-400 mt-1">Anyone with the link can pay</p>
        </div>
        @endif
    </div>
</div>

{{-- Line Items --}}
@if($invoice->invoiceDetails->count() > 0)
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Line Items</h3>
    </div>
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Product</th>
                <th class="text-left">Title</th>
                <th class="text-right">Fee</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->invoiceDetails as $detail)
            <tr>
                <td class="text-sm text-gray-700">{{ $detail->product->name ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $detail->title }}</td>
                <td class="text-right font-semibold text-sm text-gray-800">AED {{ number_format($detail->fee, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="2" class="text-right font-semibold text-sm text-gray-700">Total</td>
                <td class="text-right font-bold text-base gradient-text">AED {{ number_format($invoice->total_fee, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

@push('scripts')
<script>
function copyPaymentLink(link) {
    const button = document.getElementById('copyButton');
    navigator.clipboard.writeText(link).then(function() {
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2000);
    }).catch(function() {
        const input = document.getElementById('paymentLink');
        input.select();
        document.execCommand('copy');
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2000);
    });
}
</script>
@endpush
@endsection
