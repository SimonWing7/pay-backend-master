@extends('merchant.layout')

@section('title', 'Product Details')
@section('page-title', 'Product Details')
@section('page-subtitle', 'Product details, the people who need to pay, and payments')

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
                <p class="text-xs text-gray-400 font-medium mb-1">Standard fee</p>
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

{{-- Payers: the people who need to pay for this product --}}
@php
    $hasPayers = $summary['invoices'] > 0;
    $filtersActive = request()->filled('q') || request()->filled('status') || request()->filled('link');
@endphp

@if($hasPayers)
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
    <div class="stat-card">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Payers</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['children']) }}</p>
        @if($summary['children'] !== $summary['invoices'])
            <p class="text-xs text-gray-400">{{ number_format($summary['invoices']) }} invoices</p>
        @endif
    </div>
    <div class="stat-card">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Paid</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['paid']) }} <span class="text-sm font-medium text-gray-400">of {{ number_format($summary['invoices']) }}</span></p>
        <p class="text-xs text-gray-400">{{ number_format($summary['unpaid']) }} still to pay</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Collected</p>
        <p class="text-2xl font-bold gradient-text">AED {{ number_format($summary['collected'], 2) }}</p>
        <p class="text-xs text-gray-400">of AED {{ number_format($summary['expected'], 2) }}</p>
    </div>
    <div class="stat-card">
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Links emailed</p>
        <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['sent']) }}</p>
        <p class="text-xs text-gray-400">
            {{ number_format($summary['not_sent']) }} not sent
            @if($summary['queued'] > 0) &middot; {{ number_format($summary['queued']) }} queued @endif
            @if($summary['failed'] > 0) &middot; <span class="text-red-600">{{ number_format($summary['failed']) }} failed</span> @endif
        </p>
    </div>
</div>
@endif

{{-- Add payers --}}
<div class="card p-6 mb-6">
    <h3 class="text-sm font-semibold text-gray-700 mb-1">{{ $hasPayers ? 'Add more payers' : 'Add the people who need to pay' }}</h3>
    <p class="text-xs text-gray-400 mb-4">
        Upload a CSV with Parent Name, Student/Player Name, Parent Email and Parent Mobile.
        Add an optional <strong>Amount</strong> column if people pay different amounts; leave it blank (or leave the column out) and they pay the standard fee of AED {{ number_format($product->fee, 2) }}.
        Each person is added to <strong>this product only</strong>, so other products and terms stay separate.
        Nothing is emailed until you click "Send payment links".
        <a href="{{ route('merchant.products.payers.template', $product->id) }}" style="color:#3d01bd;">Download a template</a>.
    </p>

    <form method="POST" action="{{ route('merchant.products.payers.import', $product->id) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3 mb-3">
        @csrf
        <input type="file" name="file" accept=".csv,.txt" required class="form-input text-sm flex-1" style="min-width:240px;">
        <button type="submit" class="btn-primary flex-shrink-0"><i class="fas fa-upload"></i> Upload CSV</button>
        <label class="flex items-start gap-2 w-full cursor-pointer">
            <input type="checkbox" name="combine" value="1" checked class="mt-0.5 h-4 w-4 rounded" style="accent-color:#3d01bd;">
            <span class="text-xs text-gray-600">
                Combine children of the same parent into one payment link
                <span class="block text-gray-400">Children who share a parent email or mobile get one email and one payment, with a line for each child. Untick to send a separate link per child.</span>
            </span>
        </label>
    </form>
    @error('file')
        <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
    @enderror

    @if(session('payer_skipped'))
    <div class="p-3 rounded-lg text-xs mb-3" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;">
        <p class="font-semibold mb-1">Rows that were skipped:</p>
        @foreach(session('payer_skipped') as $skipped)
            <div>Row {{ $skipped['row'] }}: {{ $skipped['reason'] }}</div>
        @endforeach
    </div>
    @endif

    <details {{ $errors->hasBag('addPayer') ? 'open' : '' }}>
        <summary class="text-xs font-medium cursor-pointer" style="color:#3d01bd;">Or add one person</summary>
        <form method="POST" action="{{ route('merchant.products.payers.store', $product->id) }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 mt-3 items-start">
            @csrf
            <div>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Parent name *" required class="form-input text-sm">
                @if($errors->addPayer->has('name'))<p class="text-red-500 text-xs mt-1">{{ $errors->addPayer->first('name') }}</p>@endif
            </div>
            <div><input type="text" name="student_name" value="{{ old('student_name') }}" placeholder="Student / player" class="form-input text-sm"></div>
            <div>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" class="form-input text-sm">
                @if($errors->addPayer->has('email'))<p class="text-red-500 text-xs mt-1">{{ $errors->addPayer->first('email') }}</p>@endif
            </div>
            <div><input type="text" name="mobile" value="{{ old('mobile') }}" placeholder="Mobile" class="form-input text-sm"></div>
            <div>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" placeholder="Amount (AED)" class="form-input text-sm">
                @if($errors->addPayer->has('amount'))<p class="text-red-500 text-xs mt-1">{{ $errors->addPayer->first('amount') }}</p>@endif
            </div>
            <button type="submit" class="btn-secondary justify-center"><i class="fas fa-plus"></i> Add</button>
        </form>
    </details>
</div>

{{-- Payers list --}}
@if($hasPayers || $filtersActive)
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <h3 class="text-sm font-semibold text-gray-700">Payers</h3>
        <div class="flex flex-wrap items-center gap-2">
            @if($summary['sendable'] > 0)
            <form method="POST" action="{{ route('merchant.products.payers.send', $product->id) }}"
                onsubmit="return confirm('Email a payment link to {{ $summary['sendable'] }} {{ $summary['sendable'] === 1 ? 'person' : 'people' }}? They go out in the background.');">
                @csrf
                <button type="submit" class="btn-primary py-2 text-sm"><i class="fas fa-paper-plane"></i> Send payment links ({{ number_format($summary['sendable']) }})</button>
            </form>
            @endif
            @if($summary['retryable'] > 0)
            <form method="POST" action="{{ route('merchant.products.payers.retry', $product->id) }}">
                @csrf
                <button type="submit" class="btn-secondary py-2 text-sm" style="color:#dc2626;"><i class="fas fa-redo"></i> Retry {{ number_format($summary['retryable']) }} failed</button>
            </form>
            @endif
            <a href="{{ route('merchant.products.payers.export', $product->id) }}" class="btn-secondary py-2 text-sm"><i class="fas fa-download"></i> Export CSV</a>
        </div>
    </div>

    @if($summary['no_email'] > 0)
    <div class="px-6 py-2 text-xs" style="background:#fffbeb;color:#92400e;">
        {{ number_format($summary['no_email']) }} unpaid {{ $summary['no_email'] === 1 ? 'payer has' : 'payers have' }} no email address, so can't be emailed. Share their links from the export instead.
    </div>
    @endif

    <form method="GET" action="{{ route('merchant.products.show', $product->id) }}" class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, student, email or mobile…" class="form-input text-sm py-2 flex-1" style="min-width:200px;">
        <select name="status" class="form-input text-sm py-2 w-36">
            <option value="">Any payment</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Not paid</option>
        </select>
        <select name="link" class="form-input text-sm py-2 w-40">
            <option value="">Any link status</option>
            <option value="not_sent" {{ request('link') === 'not_sent' ? 'selected' : '' }}>Not sent</option>
            <option value="queued" {{ request('link') === 'queued' ? 'selected' : '' }}>Queued</option>
            <option value="sent" {{ request('link') === 'sent' ? 'selected' : '' }}>Sent</option>
            <option value="failed" {{ request('link') === 'failed' ? 'selected' : '' }}>Failed</option>
            <option value="no_email" {{ request('link') === 'no_email' ? 'selected' : '' }}>No email</option>
        </select>
        <button type="submit" class="btn-primary py-2 text-sm"><i class="fas fa-filter"></i> Filter</button>
        @if($filtersActive)
            <a href="{{ route('merchant.products.show', $product->id) }}" class="btn-secondary py-2 text-sm">Clear</a>
        @endif
    </form>

    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Parent</th>
                <th class="text-left">Children</th>
                <th class="text-left">Contact</th>
                <th class="text-right">Amount</th>
                <th class="text-left">Link</th>
                <th class="text-left">Payment</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payers as $invoice)
            @php
                $children = $invoice->invoiceDetails
                    ->map(fn ($line) => $line->consumer?->student_name ?: $line->consumer?->name)
                    ->filter()->unique()->values();
                if ($children->isEmpty() && $invoice->consumer?->student_name) {
                    $children = collect([$invoice->consumer->student_name]);
                }
                $hasEmail = !empty($invoice->consumer?->email);
                $unpaid = in_array($invoice->status->value, [0, 20], true);
            @endphp
            <tr>
                <td class="text-sm font-medium text-gray-700">{{ $invoice->consumer?->name ?? '—' }}</td>
                <td class="text-sm text-gray-600">
                    @forelse($children as $child)
                        <div>{{ $child }}</div>
                    @empty
                        —
                    @endforelse
                </td>
                <td>
                    <div class="text-sm text-gray-600">{{ $invoice->consumer?->email ?: '—' }}</div>
                    @if($invoice->consumer?->mobile_number)
                        <div class="text-xs text-gray-400">{{ $invoice->consumer->mobile_number }}</div>
                    @endif
                </td>
                <td class="text-sm font-semibold text-gray-800 text-right">{{ number_format($invoice->total_fee, 2) }}</td>
                <td>
                    @if(!$hasEmail && $unpaid)
                        <span class="badge-warning">No email</span>
                    @elseif($invoice->link_email_status === 'not_sent')
                        <span class="badge-muted">Not sent</span>
                    @elseif(in_array($invoice->link_email_status, ['queued', 'sending'], true))
                        <span class="badge-info">Queued</span>
                    @elseif($invoice->link_email_status === 'failed')
                        <span class="badge-danger" title="{{ $invoice->link_email_error }}">Failed</span>
                    @else
                        <span class="badge-success">Sent</span>
                        @if($invoice->link_emailed_at)
                            <div class="text-xs text-gray-400">{{ $invoice->link_emailed_at->copy()->setTimezone('Asia/Dubai')->format('j M, H:i') }}</div>
                        @endif
                    @endif
                </td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">Paid</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">{{ $invoice->status->label() }}</span>
                    @else
                        <span class="badge-warning">Not paid</span>
                    @endif
                </td>
                <td class="text-right whitespace-nowrap">
                    @if($unpaid && $hasEmail)
                    <form method="POST" action="{{ route('merchant.invoices.resend-link', $invoice->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-blue-600 transition-colors text-xs font-medium mr-2" title="Email this link now">
                            <i class="fas fa-paper-plane"></i> {{ in_array($invoice->link_email_status, ['sent', null], true) ? 'Resend' : 'Send' }}
                        </button>
                    </form>
                    @endif
                    @if($unpaid)
                    <form method="POST" action="{{ route('merchant.products.payers.remove', [$product->id, $invoice->id]) }}" class="inline"
                        onsubmit="return confirm('Remove this payer from {{ addslashes($product->name) }}? Their payment link will stop working.');">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors text-xs" title="Remove from this product"><i class="fas fa-trash"></i></button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-sm text-gray-400">No payers match those filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($payers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $payers->links() }}</div>
    @endif
    <div class="px-6 py-3 text-xs text-gray-400 border-t border-gray-100">
        Showing {{ $payers->firstItem() ?? 0 }}–{{ $payers->lastItem() ?? 0 }} of {{ number_format($payers->total()) }}
    </div>
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
