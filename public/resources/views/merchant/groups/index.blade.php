@extends('merchant.layout')

@section('title', 'Groups')
@section('page-title', 'Groups')
@section('page-subtitle', 'Organise individuals into groups')

@section('topbar-actions')
    <a href="{{ route('merchant.groups.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i> New Group
    </a>
@endsection

@section('content')
<div class="card overflow-hidden">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Group</th>
                <th class="text-left">Colour</th>
                <th class="text-left">Individuals</th>
                <th class="text-left">Created</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($groups as $group)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('merchant.groups.show', $group->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div style="width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:{{ $group->color }}20;flex-shrink:0;">
                            <i class="fas fa-layer-group text-sm" style="color:{{ $group->color }};"></i>
                        </div>
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;border-radius:50%;background:{{ $group->color }};display:inline-block;flex-shrink:0;"></span>
                            <span class="font-semibold text-sm text-gray-800">{{ $group->name }}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-xs font-mono text-gray-500">{{ $group->color }}</span>
                </td>
                <td>
                    <span class="text-sm text-gray-600">{{ $group->consumers->count() }} individual{{ $group->consumers->count() !== 1 ? 's' : '' }}</span>
                </td>
                <td class="text-sm text-gray-500">{{ $group->created_at->format('d M Y') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('merchant.groups.show', $group->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                        <a href="{{ route('merchant.groups.edit', $group->id) }}" class="text-gray-400 hover:text-amber-600 transition-colors" title="Edit">
                            <i class="fas fa-edit text-sm"></i>
                        </a>
                        <form method="POST" action="{{ route('merchant.groups.delete', $group->id) }}" class="inline"
                            onsubmit="event.stopPropagation(); return confirm('Delete this group? Individuals will not be removed.');">
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
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <p class="text-gray-500 font-medium mb-1">No groups yet</p>
                    <p class="text-gray-400 text-sm mb-4">Groups help you organise individuals such as classes or year groups</p>
                    <a href="{{ route('merchant.groups.create') }}" class="btn-primary">
                        <i class="fas fa-plus"></i> Create Group
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
