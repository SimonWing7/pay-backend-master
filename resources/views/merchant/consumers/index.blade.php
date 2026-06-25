@extends('merchant.layout')

@section('title', 'Individuals')
@section('page-title', 'Individuals')
@section('page-subtitle', 'Manage your customers and individual payers')

@section('topbar-actions')
    <a href="{{ route('merchant.consumers.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> Add Individual
    </a>
@endsection

@section('content')

<x-filter-sort
    :route="route('merchant.consumers.index')"
    :filters="[
        'search' => true,
        'group_id' => true,
        'groups' => $groups ?? [],
        'sort_options' => ['name' => 'Name', 'email' => 'Email', 'created_at' => 'Created At', 'updated_at' => 'Updated At']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Individual</th>
                <th class="text-left">Contact</th>
                <th class="text-left">Groups</th>
                <th class="text-left">Added</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consumers as $consumer)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.consumers.show', $consumer->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:50%;flex-shrink:0;">
                            {{ strtoupper(substr($consumer->name, 0, 1)) }}
                        </div>
                        <div class="font-semibold text-sm text-gray-800">{{ $consumer->name }}</div>
                    </div>
                </td>
                <td>
                    <div class="text-sm text-gray-600">{{ $consumer->email ?? '—' }}</div>
                    @if($consumer->mobile_number)
                    <div class="text-xs text-gray-400 mt-0.5">{{ $consumer->mobile_number }}</div>
                    @endif
                </td>
                <td>
                    @if($consumer->groups->count() > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach($consumer->groups as $group)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full"
                                    style="background-color: {{ $group->color }}20; color: {{ $group->color }}; border: 1px solid {{ $group->color }}40;">
                                    {{ $group->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $consumer->created_at->format('d M Y') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('merchant.consumers.show', $consumer->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        <a href="{{ route('merchant.consumers.edit', $consumer->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('merchant.consumers.delete', $consumer->id) }}" class="inline"
                            onsubmit="event.stopPropagation(); return confirm('Remove this individual?');">
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
                <td colspan="5" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">No individuals yet</p>
                    <p class="text-gray-400 text-sm mb-4">Add individuals to send personal payment links</p>
                    <a href="{{ route('merchant.consumers.create') }}" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Individual
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($consumers->hasPages())
<div class="mt-4">{{ $consumers->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $consumers->firstItem() ?? 0 }}–{{ $consumers->lastItem() ?? 0 }} of {{ $consumers->total() }} results
</div>
@endsection
