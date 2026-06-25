@extends('merchant.layout')

@section('title', 'Payments')
@section('page-title', 'Payments')
@section('page-subtitle', 'All payment transactions')

@section('topbar-actions')
    <a href="{{ route('merchant.payments.export.csv', request()->query()) }}" class="btn-secondary">
        <i class="fas fa-download"></i> Export CSV
    </a>
@endsection

@section('content')

{{-- Compact filter bar --}}
<div class="card p-4 mb-4">
    <form method="GET" action="{{ route('merchant.payments.index') }}" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Search by name or token…"
            class="form-input flex-1 min-w-48 text-sm py-2">

        <select name="status" class="form-input text-sm py-2 w-40">
            <option value="">All Statuses</option>
            <option value="0"  {{ request('status') === '0'  ? 'selected' : '' }}>Initiated</option>
            <option value="10" {{ request('status') === '10' ? 'selected' : '' }}>Complete</option>
            <option value="20" {{ request('status') === '20' ? 'selected' : '' }}>Failed</option>
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}"
            class="form-input text-sm py-2 w-36" title="From date">

        <input type="date" name="date_to" value="{{ request('date_to') }}"
            class="form-input text-sm py-2 w-36" title="To date">

        <select name="sort_by" class="form-input text-sm py-2 w-36">
            <option value="created_at" {{ request('sort_by', 'created_at') === 'created_at' ? 'selected' : '' }}>Date Created</option>
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
            <a href="{{ route('merchant.payments.index') }}" class="btn-secondary py-2 text-sm">Clear</a>
        </div>
    </form>
</div>

<div class="card overflow-hidden">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Payment</th>
                <th class="text-left">Individual</th>
                <th class="text-left">Payment Link</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Status</th>
                <th class="text-left">Date</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            @php $title = $payment->invoice->invoiceDetails->first()?->title ?? null; @endphp
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
                <td class="text-sm text-gray-700">{{ $title ?? '—' }}</td>
                <td class="font-semibold text-sm text-gray-800">AED {{ number_format($payment->invoice->total_fee ?? 0, 2) }}</td>
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
                <td colspan="7" class="text-center py-16">
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
