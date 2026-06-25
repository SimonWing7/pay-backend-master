@extends('merchant.layout')

@section('title', 'Group Details')
@section('page-title', 'Group Details')
@section('page-subtitle', 'View group members and linked payment links')

@section('topbar-actions')
    <a href="{{ route('merchant.groups.edit', $group->id) }}" class="btn-secondary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="{{ route('merchant.groups.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')

{{-- Group header card --}}
<div class="card p-6 mb-6">
    <div class="flex items-center gap-4">
        <div style="width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:{{ $group->color }}20;flex-shrink:0;">
            <i class="fas fa-layer-group text-xl" style="color:{{ $group->color }};"></i>
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-3">
                <span style="width:12px;height:12px;border-radius:50%;background:{{ $group->color }};display:inline-block;"></span>
                <h2 class="text-xl font-bold text-gray-800">{{ $group->name }}</h2>
            </div>
            <p class="text-sm text-gray-500 mt-1">Colour: <span class="font-mono">{{ $group->color }}</span></p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-400">Created</p>
            <p class="text-sm text-gray-600">{{ $group->created_at->format('d M Y') }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Individuals</p>
                <p class="text-2xl font-bold text-gray-800">{{ $group->consumers->count() }}</p>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="flex items-center gap-4">
            <div class="stat-icon"><i class="fas fa-link"></i></div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Payment Links</p>
                <p class="text-2xl font-bold text-gray-800">{{ $group->invoices->count() }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Individuals --}}
<div class="card overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-700">Individuals in this Group</h3>
        <a href="{{ route('merchant.groups.edit', $group->id) }}" class="text-xs text-purple-600 font-medium hover:underline">Manage</a>
    </div>
    @if($group->consumers->count() > 0)
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Email</th>
                <th class="text-left">Mobile</th>
                <th class="text-right">View</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group->consumers as $consumer)
            <tr>
                <td class="font-medium text-sm text-gray-800">{{ $consumer->name }}</td>
                <td class="text-sm text-gray-500">{{ $consumer->email ?? '—' }}</td>
                <td class="text-sm text-gray-500">{{ $consumer->mobile_number ?? '—' }}</td>
                <td class="text-right">
                    <a href="{{ route('merchant.consumers.show', $consumer->id) }}" class="text-gray-400 hover:text-blue-600">
                        <i class="fas fa-eye text-sm"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="text-center py-10">
        <p class="text-gray-400 text-sm">No individuals in this group yet.</p>
        <a href="{{ route('merchant.groups.edit', $group->id) }}" class="btn-secondary mt-3 text-sm">Add Individuals</a>
    </div>
    @endif
</div>

{{-- Payment Links --}}
@if($group->invoices->count() > 0)
<div class="card overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Payment Links</h3>
    </div>
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Link ID</th>
                <th class="text-left">Individual</th>
                <th class="text-left">Amount</th>
                <th class="text-left">Status</th>
                <th class="text-right">View</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group->invoices as $invoice)
            <tr>
                <td class="font-medium text-sm">#{{ $invoice->id }}</td>
                <td class="text-sm text-gray-600">{{ $invoice->consumer->name ?? '—' }}</td>
                <td class="text-sm font-semibold">AED {{ number_format($invoice->total_fee, 2) }}</td>
                <td>
                    @if($invoice->status->value === 10)
                        <span class="badge-success">Paid</span>
                    @elseif($invoice->status->value === 20)
                        <span class="badge-danger">Failed</span>
                    @else
                        <span class="badge-warning">{{ $invoice->status->label() }}</span>
                    @endif
                </td>
                <td class="text-right">
                    <a href="{{ route('merchant.invoices.show', $invoice->id) }}" class="text-gray-400 hover:text-blue-600">
                        <i class="fas fa-eye text-sm"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
