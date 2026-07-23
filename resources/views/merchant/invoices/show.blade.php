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
    <div class="flex flex-col lg:flex-row gap-6 items-start">

        {{-- Link + actions --}}
        <div class="flex-1">
            <p class="form-label mb-2">Payment Link</p>
            <div class="flex items-center gap-3 mb-3">
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
            <p class="text-xs text-gray-400">Share this link via WhatsApp, email, or display the QR code for in-person payments.</p>
        </div>

        {{-- QR Code --}}
        <div class="flex flex-col items-center gap-3 flex-shrink-0">
            <div id="qrcode" class="p-3 bg-white border border-gray-200 rounded-xl shadow-sm"></div>
            <button onclick="printQR()" class="btn-secondary text-sm py-2 px-4">
                <i class="fas fa-print"></i> Print QR
            </button>
        </div>

    </div>
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
            @if($invoice->reference)
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Reference</p>
                <p class="text-sm font-mono font-semibold text-gray-800">{{ $invoice->reference }}</p>
            </div>
            @endif
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

{{-- Payments on this invoice --}}
@if($invoice->appUserPayments && $invoice->appUserPayments->count() > 0)
<div class="card overflow-hidden mt-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($invoice->appUserPayments as $payment)
        <div class="px-6 py-5">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="stat-icon" style="width:36px;height:36px;font-size:13px;border-radius:8px;flex-shrink:0;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">#{{ $payment->id }}</p>
                        <p class="text-xs text-gray-400 font-mono">{{ substr($payment->token ?? '', 0, 16) }}{{ strlen($payment->token ?? '') > 16 ? '…' : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">{{ $payment->created_at->format('d M Y, H:i') }}</span>
                    @if($payment->status->value === 10)
                        <span class="badge-success">{{ $payment->status->label() }}</span>
                    @elseif($payment->status->value === 20)
                        <span class="badge-danger">{{ $payment->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $payment->status->label() }}</span>
                    @endif
                </div>
            </div>

            {{-- Customer details collected at payment time --}}
            @if($payment->customer_name || $payment->customer_email || $payment->customer_mobile || $payment->custom_field_values)
            <div class="mt-3 rounded-xl p-4" style="background:#f8f9ff;border:1px solid #eef0f5;">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Customer Details</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @if($payment->customer_name)
                    <div>
                        <p class="text-xs text-gray-400 font-medium mb-0.5">Name</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $payment->customer_name }}</p>
                    </div>
                    @endif
                    @if($payment->customer_email)
                    <div>
                        <p class="text-xs text-gray-400 font-medium mb-0.5">Email</p>
                        <p class="text-sm text-gray-700">{{ $payment->customer_email }}</p>
                    </div>
                    @endif
                    @if($payment->customer_mobile)
                    <div>
                        <p class="text-xs text-gray-400 font-medium mb-0.5">Mobile</p>
                        <p class="text-sm text-gray-700">{{ $payment->customer_mobile }}</p>
                    </div>
                    @endif
                </div>
                @if($payment->custom_field_values && count($payment->custom_field_values) > 0)
                <div class="mt-3 pt-3 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($payment->custom_field_values as $label => $value)
                    <div>
                        <p class="text-xs text-gray-400 font-medium mb-0.5">{{ $label }}</p>
                        <p class="text-sm text-gray-700">{{ $value ?: '—' }}</p>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
const paymentUrl = "{{ route('public.invoice.show', $invoice->uuid) }}";

// Generate QR code
new QRCode(document.getElementById('qrcode'), {
    text: paymentUrl,
    width: 160,
    height: 160,
    colorDark: '#000026',
    colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
});

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

function printQR() {
    const qrCanvas = document.querySelector('#qrcode canvas') || document.querySelector('#qrcode img');
    const title = "{{ addslashes($invoice->invoiceDetails->first()?->title ?? 'Payment') }}";
    const amount = "AED {{ number_format($invoice->total_fee, 2) }}";
    const win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>QR Code — ${title}</title>
        <style>
            body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            h2 { color: #000026; margin-bottom: 4px; }
            p { color: #6b7280; margin: 0 0 20px; font-size: 14px; }
        </style></head>
        <body>
            <h2>${title}</h2>
            <p>${amount}</p>
            ${qrCanvas ? (qrCanvas.tagName === 'CANVAS' ? `<img src="${qrCanvas.toDataURL()}" width="240">` : `<img src="${qrCanvas.src}" width="240">`) : ''}
            <p style="margin-top:16px;font-size:12px;color:#9ca3af;">Scan to pay via Edfundo Pay</p>
        </body></html>
    `);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
}
</script>
@endpush
@endsection
