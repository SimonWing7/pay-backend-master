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

<x-filter-sort
    :route="route('merchant.invoices.index')"
    :filters="[
        'search' => true,
        'status' => true,
        'consumer_id' => true,
        'group_id' => true,
        'date_from' => true,
        'date_to' => true,
        'status_options' => [0 => 'Draft', 10 => 'Paid', 20 => 'Failed'],
        'consumers' => $consumers ?? [],
        'groups' => $groups ?? [],
        'sort_options' => ['created_at' => 'Created At', 'updated_at' => 'Updated At', 'total_fee' => 'Total Fee', 'status' => 'Status']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
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
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.invoices.show', $invoice->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:8px;flex-shrink:0;">
                            <i class="fas fa-link"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800 text-sm">#{{ $invoice->id }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ substr($invoice->uuid, 0, 16) }}…</div>
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">{{ $invoice->consumer->name ?? '—' }}</td>
                <td class="font-semibold text-sm text-gray-800">AED {{ number_format($invoice->total_fee, 2) }}</td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">Paid</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">Failed</span>
                    @else
                        <span class="badge-warning">{{ $invoice->status->label() }}</span>
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
                        <a href="{{ route('merchant.invoices.edit', $invoice->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('merchant.invoices.delete', $invoice->id) }}" class="inline" onsubmit="event.stopPropagation(); return confirm('Delete this payment link?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-link"></i>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">No payment links yet</p>
                    <p class="text-gray-400 text-sm mb-4">Create your first payment link to start collecting payments</p>
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
        setTimeout(function() {
            button.innerHTML = '<i class="fas fa-link text-sm"></i>';
        }, 2000);
    }).catch(function() {
        const ta = document.createElement('textarea');
        ta.value = link;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        button.innerHTML = '<i class="fas fa-check text-green-500 text-sm"></i>';
        setTimeout(function() {
            button.innerHTML = '<i class="fas fa-link text-sm"></i>';
        }, 2000);
    });
}
</script>
@endpush
@endsection
