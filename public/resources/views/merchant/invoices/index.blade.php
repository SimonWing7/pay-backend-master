@extends('merchant.layout')

@section('title', 'Payment Links')
@section('page-title', 'Payment Links')
@section('page-subtitle', 'Manage and share your payment links')

@section('topbar-actions')
    <a href="{{ route('merchant.invoices.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Payment Link
    </a>
@endsection

@section('content')

{{-- Compact filter bar --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('merchant.invoices.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by name or individual…"
            class="form-input flex-1 min-w-48 text-sm py-2">

        <select name="status" class="form-input text-sm py-2 w-40">
            <option value="">All Active</option>
            <option value="0"  {{ request('status') === '0'  ? 'selected' : '' }}>Draft</option>
            <option value="10" {{ request('status') === '10' ? 'selected' : '' }}>Paid</option>
            <option value="20" {{ request('status') === '20' ? 'selected' : '' }}>Failed</option>
            <option value="30" {{ request('status') === '30' ? 'selected' : '' }}>Archived</option>
        </select>

        <select name="consumer_id" class="form-input text-sm py-2 w-44">
            <option value="">All Individuals</option>
            @foreach($consumers as $consumer)
                <option value="{{ $consumer->id }}" {{ request('consumer_id') == $consumer->id ? 'selected' : '' }}>
                    {{ $consumer->name }}
                </option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="form-input text-sm py-2 w-36" title="From date">

        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="form-input text-sm py-2 w-36" title="To date">

        <select name="sort_by" class="form-input text-sm py-2 w-36">
            <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date Created</option>
            <option value="total_fee"  {{ request('sort_by') === 'total_fee'  ? 'selected' : '' }}>Amount</option>
            <option value="status"     {{ request('sort_by') === 'status'     ? 'selected' : '' }}>Status</option>
        </select>

        <select name="sort_dir" class="form-input text-sm py-2 w-32">
            <option value="desc" {{ request('sort_dir', 'desc') === 'desc' ? 'selected' : '' }}>Newest first</option>
            <option value="asc"  {{ request('sort_dir') === 'asc'          ? 'selected' : '' }}>Oldest first</option>
        </select>

        <input type="hidden" name="per_page" value="{{ request('per_page', 15) }}">

        <div class="flex gap-2">
            <button type="submit" class="btn-primary py-2 text-sm">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="{{ route('merchant.invoices.index') }}" class="btn-secondary py-2 text-sm">
                Clear
            </a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Individual</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Status</th>
                <th class="text-left">Created</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            @php $title = $invoice->invoiceDetails->first()?->title ?? null; @endphp
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.invoices.show', $invoice->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:8px;flex-shrink:0;">
                            <i class="fas fa-link"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800 text-sm">{{ $title ?? '—' }}</div>
                            <div class="text-xs text-gray-400">#{{ $invoice->id }}</div>
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">
                    @if($invoice->consumer)
                        {{ $invoice->consumer->name }}
                    @else
                        <span class="text-xs text-gray-400 italic">Open link</span>
                    @endif
                </td>
                <td class="font-semibold text-sm text-gray-800">AED {{ number_format($invoice->total_fee, 2) }}</td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">Paid</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">Failed</span>
                    @elseif($invoice->status->value === 30)
                        <span class="badge-warning" style="background:rgba(107,114,128,0.1);color:#374151;">Archived</span>
                    @else
                        <span class="badge-warning">Draft</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $invoice->created_at->format('d M Y') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex items-center justify-end gap-3">
                        <button
                            onclick="copyPaymentLink('{{ route('public.invoice.show', $invoice->uuid) }}', '{{ $invoice->id }}')"
                            class="text-gray-400 hover:text-purple-600 transition-colors"
                            title="Copy Payment Link"
                            id="copyBtn{{ $invoice->id }}">
                            <i class="fas fa-link text-sm"></i>
                        </button>
                        <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        @if($invoice->status->value !== 10)
                        <a href="{{ route('merchant.invoices.edit', $invoice->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        @endif
                        @if($invoice->status->value === 30)
                            {{-- Unarchive --}}
                            <form method="POST" action="{{ route('merchant.invoices.unarchive', $invoice->id) }}" class="inline" onsubmit="event.stopPropagation();">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-green-600 transition-colors" title="Restore">
                                    <i class="fas fa-undo text-sm"></i>
                                </button>
                            </form>
                        @else
                            {{-- Archive --}}
                            <form method="POST" action="{{ route('merchant.invoices.archive', $invoice->id) }}" class="inline" onsubmit="event.stopPropagation();">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-gray-600 transition-colors" title="Archive">
                                    <i class="fas fa-archive text-sm"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-link"></i>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">No payment links found</p>
                    <p class="text-gray-400 text-sm mb-4">Try adjusting your filters, or create your first payment link</p>
                    <a href="{{ route('merchant.invoices.create') }}" class="btn-primary">
                        <i class="fas fa-plus"></i> Create Payment Link
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($invoices->hasPages())
<div class="mt-4">{{ $invoices->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $invoices->firstItem() ?? 0 }}–{{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} results
</div>

@push('scripts')
<script>
function copyPaymentLink(link, invoiceId) {
    const button = document.getElementById('copyBtn' + invoiceId);
    navigator.clipboard.writeText(link).then(function() {
        button.innerHTML = '<i class="fas fa-check text-green-500 text-sm"></i>';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-link text-sm"></i>'; }, 2000);
    }).catch(function() {
        const ta = document.createElement('textarea');
        ta.value = link;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        button.innerHTML = '<i class="fas fa-check text-green-500 text-sm"></i>';
        setTimeout(function() { button.innerHTML = '<i class="fas fa-link text-sm"></i>'; }, 2000);
    });
}
</script>
@endpush
@endsection
