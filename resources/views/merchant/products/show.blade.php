@extends('merchant.layout')

@section('title', 'Product Details')
@section('page-title', 'Product Details')
@section('page-subtitle', 'View product information and payments')

@section('topbar-actions')
    <form method="POST" action="{{ route('merchant.products.toggle-state', $product->id) }}" class="inline"
        onsubmit="return confirm('{{ $product->state === 'active' ? 'Archive' : 'Activate' }} this product?');">
        @csrf
        <button type="submit" class="btn-secondary">
            <i class="fas {{ $product->state === 'active' ? 'fa-archive' : 'fa-check-circle' }}"></i>
            {{ $product->state === 'active' ? 'Archive' : 'Activate' }}
        </button>
    </form>
    <a href="{{ route('merchant.products.edit', $product->id) }}" class="btn-secondary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <form method="POST" action="{{ route('merchant.products.delete', $product->id) }}" class="inline"
        onsubmit="return confirm('Delete this product? If it has any payments against it, it will be archived instead of deleted.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-secondary" style="color:#dc2626;">
            <i class="fas fa-trash"></i> Delete
        </button>
    </form>
    <a href="{{ route('merchant.products.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

{{-- Product Link Banner --}}
<div class="card p-6 mb-6">
    <p class="form-label mb-2">Product Link</p>
    <div class="flex items-center gap-3">
        <input type="text"
            id="productLink"
            value="{{ route('public.product', $product->uuid) }}"
            readonly
            class="form-input bg-gray-50 font-mono text-sm flex-1">
        <button onclick="copyProductLink('{{ route('public.product', $product->uuid) }}')"
            class="btn-primary flex-shrink-0" id="copyButton">
            <i class="fas fa-copy"></i> Copy
        </button>
    </div>
    <p class="text-xs text-gray-400 mt-2">Share this link with customers to purchase this product</p>
</div>

{{-- Info + Stats --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Product Info</h3>
        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Name</p>
                <p class="text-base font-bold text-gray-800">{{ $product->name }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Description</p>
                <p class="text-sm text-gray-600">{{ $product->description }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Fee</p>
                <p class="text-2xl font-bold gradient-text">AED {{ number_format($product->fee, 2) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Status</p>
                @if($product->state === 'active')
                    <span class="badge-success">Active</span>
                @else
                    <span class="badge-info">Archived</span>
                @endif
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">Created</p>
                <p class="text-sm text-gray-600">{{ $product->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="stat-card">
            <div class="flex items-center gap-4">
                <div class="stat-icon"><i class="fas fa-credit-card"></i></div>
                <div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Payments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalPayments }}</p>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="flex items-center gap-4">
                <div class="stat-icon"><i class="fas fa-coins"></i></div>
                <div>
                    <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Total Collected</p>
                    <p class="text-2xl font-bold gradient-text">AED {{ number_format($totalAmount, 2) }}</p>
                </div>
            </div>
        </div>
        @if($payments->count() > 0)
        <a href="{{ route('merchant.products.payments.export', $product->id) }}" class="btn-secondary w-full justify-center">
            <i class="fas fa-download"></i> Export Payments CSV
        </a>
        @endif
    </div>
</div>

{{-- Invoice Tracking --}}
@if($invoices->count() > 0)
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Invoice Tracking</h3>
        <span class="text-xs text-gray-400">{{ $invoices->where('status', \App\Enums\InvoiceStatus::Paid)->count() }} paid of {{ $invoices->count() }}</span>
    </div>
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Parent</th>
                <th class="text-left">Student</th>
                <th class="text-left">Contact</th>
                <th class="text-left">Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
            <tr>
                <td class="text-sm font-medium text-gray-700">{{ $invoice->consumer?->name ?? '—' }}</td>
                <td class="text-sm text-gray-600">{{ $invoice->consumer?->student_name ?? '—' }}</td>
                <td>
                    <div class="text-sm text-gray-600">{{ $invoice->consumer?->email ?? '—' }}</div>
                    @if($invoice->consumer?->mobile_number)
                        <div class="text-xs text-gray-400">{{ $invoice->consumer->mobile_number }}</div>
                    @endif
                </td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">{{ $invoice->status->label() }}</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">{{ $invoice->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $invoice->status->label() }}</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($invoice->status->value !== 10 && ($invoice->consumer?->email))
                    <form method="POST" action="{{ route('merchant.invoices.resend-link', $invoice->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-blue-600 transition-colors text-xs font-medium">
                            <i class="fas fa-paper-plane"></i> Resend
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Reminders --}}
<div class="card p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-1">Payment Reminders</h3>
    <p class="text-xs text-gray-400 mb-4">Scheduled emails sent to anyone still unpaid under this product on the chosen date. Already-paid individuals are automatically skipped.</p>

    @if($reminders->count() > 0)
    <div class="space-y-2 mb-4">
        @foreach($reminders as $reminder)
        <div class="flex items-start justify-between gap-3 border border-gray-100 rounded-lg p-3">
            <div>
                <div class="text-sm font-medium text-gray-700">
                    {{ $reminder->send_date->format('d M Y') }}
                    @if($reminder->sent_at)
                        <span class="badge-success ml-2">Sent</span>
                    @else
                        <span class="badge-info ml-2">Scheduled</span>
                    @endif
                </div>
                <p class="text-xs text-gray-500 mt-1 whitespace-pre-line">{{ $reminder->message }}</p>
            </div>
            @unless($reminder->sent_at)
            <form method="POST" action="{{ route('merchant.products.reminders.destroy', [$product->id, $reminder->id]) }}"
                onsubmit="return confirm('Remove this scheduled reminder?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors flex-shrink-0" title="Remove">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </form>
            @endunless
        </div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('merchant.products.reminders.store', $product->id) }}" class="flex items-end gap-3">
        @csrf
        <div class="w-40">
            <label class="form-label">Date</label>
            <input type="date" name="send_date" required min="{{ now()->format('Y-m-d') }}" class="form-input @error('send_date') border-red-400 @enderror">
            @error('send_date')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex-1">
            <label class="form-label">Message</label>
            <textarea name="message" rows="2" required placeholder="e.g. Friendly reminder that Term 1 fees are due — please pay at your earliest convenience."
                class="form-input @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
            @error('message')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-primary flex-shrink-0">
            <i class="fas fa-bell"></i> Schedule
        </button>
    </form>
</div>

{{-- Payments Table --}}
@if($payments->count() > 0)
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Payments</h3>
    </div>
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment ID</th>
                <th class="text-left">Individual</th>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Status</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td class="font-medium text-sm">#{{ $payment->id }}</td>
                <td class="text-sm text-gray-600">{{ $payment->invoice->consumer->name ?? '—' }}</td>
                <td class="text-sm font-mono text-gray-400">{{ substr($payment->invoice->uuid ?? '', 0, 12) }}…</td>
                <td>
                    @if($payment->status->value === 10)
                        <span class="badge-success">{{ $payment->status->label() }}</span>
                    @elseif($payment->status->value === 20)
                        <span class="badge-danger">{{ $payment->status->label() }}</span>
                    @elseif($payment->status->value === 30)
                        <span class="badge-muted">{{ $payment->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $payment->status->label() }}</span>
                    @endif
                </td>
                <td class="font-semibold text-sm">AED {{ number_format($payment->invoice->total_fee ?? 0, 2) }}</td>
                <td class="text-sm text-gray-500">{{ $payment->created_at->format('d M Y') }}</td>
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
    <p class="text-gray-400 text-sm mt-1">Share the product link above to start collecting</p>
</div>
@endif

@push('scripts')
<script>
function copyProductLink(link) {
    const button = document.getElementById('copyButton');
    navigator.clipboard.writeText(link).then(function() {
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2000);
    }).catch(function() {
        const input = document.getElementById('productLink');
        input.select();
        document.execCommand('copy');
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-copy"></i> Copy'; }, 2000);
    });
}
</script>
@endpush
@endsection
