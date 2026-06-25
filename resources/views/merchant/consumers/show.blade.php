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
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Groups</h3>
        @if($consumer->groups->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($consumer->groups as $group)
                    <a href="{{ route('merchant.groups.show', $group->id) }}"
                        class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                        style="background-color: {{ $group->color }}15; color: {{ $group->color }}; border: 1px solid {{ $group->color }}30;">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $group->color }};display:inline-block;"></span>
                        {{ $group->name }}
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">Not in any groups yet.</p>
            <a href="{{ route('merchant.groups.index') }}" class="btn-secondary mt-3 text-sm">View Groups</a>
        @endif
    </div>
</div>
@endsection
