@extends('merchant.layout')

@section('title', 'Individual Details')
@section('page-title', 'Individual Details')
@section('page-subtitle', 'View individual information')

@section('topbar-actions')
    <a href="{{ route('merchant.consumers.edit', $consumer->id) }}" class="btn-secondary">
        <i class="fas fa-edit"></i> Edit
    </a>
    <a href="{{ route('merchant.invoices.create') }}?consumer_id={{ $consumer->id }}" class="btn-primary">
        <i class="fas fa-link"></i> Create Payment Link
    </a>
    <a href="{{ route('merchant.consumers.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="grid grid-cols-1 gap-6 max-w-lg">
    <div class="card p-6">
        <div class="flex items-center gap-4 mb-6">
            <div class="stat-icon" style="width:52px;height:52px;font-size:20px;border-radius:50%;">
                {{ strtoupper(substr($consumer->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">{{ $consumer->name }}</h2>
                <p class="text-sm text-gray-500">Individual</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Email</p>
                <p class="text-sm text-gray-800">{{ $consumer->email ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Mobile</p>
                <p class="text-sm text-gray-800">{{ $consumer->mobile_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Added</p>
                <p class="text-sm text-gray-800">{{ $consumer->created_at->format('d M Y, H:i') }}</p>
            </div>
        </div>
    </div>

</div>
@endsection
