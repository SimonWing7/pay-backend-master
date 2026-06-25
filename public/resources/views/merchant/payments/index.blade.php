@extends('merchant.layout')

@section('title', 'Payments')
@section('page-title', 'Payments')
@section('page-subtitle', 'All payment transactions')

@section('content')

<x-filter-sort
    :route="route('merchant.payments.index')"
    :filters="[
        'search' => true,
        'status' => true,
        'date_from' => true,
        'date_to' => true,
        'status_options' => [0 => 'Initiated', 10 => 'Complete', 20 => 'Failed'],
        'sort_options' => ['created_at' => 'Created At', 'updated_at' => 'Updated At', 'status' => 'Status']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment</th>
                <th class="text-left">Individual</th>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Status</th>
                <th class="text-left">Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.payments.show', $payment->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:8px;flex-shrink:0;">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-gray-800">#{{ $payment->id }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ substr($payment->token, 0, 16) }}…</div>
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">{{ $payment->invoice->consumer->name ?? '—' }}</td>
                <td class="text-sm font-mono text-gray-400">{{ substr($payment->invoice->uuid ?? '', 0, 12) }}…</td>
                <td>
                    @if($payment->status->value === 10)
                        <span class="badge-success">{{ $payment->status->label() }}</span>
                    @elseif($payment->status->value === 20)
                        <span class="badge-danger">{{ $payment->status->label() }}</span>
                    @else
                        <span class="badge-warning">{{ $payment->status->label() }}</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $payment->created_at->format('d M Y, H:i') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex justify-end">
                        <a href="{{ route('merchant.payments.show', $payment->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No payments yet</p>
                    <p class="text-gray-400 text-sm mt-1">Payments will appear here once customers start paying</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payments->hasPages())
<div class="mt-4">{{ $payments->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ $payments->total() }} results
</div>
@endsection
