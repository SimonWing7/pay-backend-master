@extends('admin.layout')

@section('title', 'Invoices')
@section('page-title', 'Invoices')
@section('page-subtitle', 'All payment links/invoices across all merchants — including ones never actually paid against')

@section('content')

<x-filter-sort
    :route="route('admin.invoices.index')"
    :filters="[
        'search' => true,
        'status' => true,
        'merchant_id' => true,
        'merchants' => $merchants,
        'date_from' => true,
        'date_to' => true,
        'status_options' => [0 => 'Pending', 10 => 'Paid', 20 => 'Failed', 30 => 'Archived'],
        'sort_options' => ['created_at' => 'Created At', 'updated_at' => 'Updated At', 'total_fee' => 'Amount', 'status' => 'Status']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Invoice</th>
                <th class="text-left">Payer</th>
                <th class="text-left">Merchant</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Status</th>
                <th class="text-left">Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:8px;flex-shrink:0;">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-gray-800">#{{ $invoice->id }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ substr($invoice->uuid, 0, 16) }}…</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-sm text-gray-700">{{ $invoice->consumer->name ?? 'Open link' }}</div>
                    <div class="text-xs text-gray-400">{{ $invoice->consumer->email ?? '' }}</div>
                </td>
                <td class="text-sm text-gray-600">{{ $invoice->merchant->name ?? '—' }}</td>
                <td class="text-sm font-semibold text-gray-800">AED {{ number_format($invoice->total_fee, 2) }}</td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">{{ $invoice->status->label() }}</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">{{ $invoice->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $invoice->status->label() }}</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $invoice->created_at->format('d M Y, H:i') }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('public.invoice.show', $invoice->uuid) }}" target="_blank" class="text-gray-400 hover:text-blue-600 transition-colors" title="View public page">
                            <i class="fas fa-external-link-alt text-sm"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No invoices found</p>
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
@endsection
