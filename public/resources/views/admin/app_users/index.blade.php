@extends('admin.layout')

@section('title', 'App Users')
@section('page-title', 'App Users')
@section('page-subtitle', 'Edfundo app users who have made payments')

@section('content')

<x-filter-sort
    :route="route('admin.app_users.index')"
    :filters="[
        'search' => true,
        'date_from' => true,
        'date_to' => true,
        'sort_options' => ['name' => 'Name', 'email' => 'Email', 'created_at' => 'Created At', 'updated_at' => 'Updated At', 'device_id' => 'Device ID']
    ]"
    :sortBy="request('sort_by', 'created_at')"
    :sortDir="request('sort_dir', 'desc')"
/>

<div class="card overflow-hidden mt-4">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">User</th>
                <th class="text-left">Email</th>
                <th class="text-left">Device ID</th>
                <th class="text-left">Payments</th>
                <th class="text-left">Joined</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appUsers as $appUser)
            <tr class="cursor-pointer" onclick="window.location.href='{{ route('admin.app_users.show', $appUser->id) }}'">
                <td>
                    <div class="flex items-center gap-3">
                        <div class="stat-icon" style="width:36px;height:36px;font-size:14px;border-radius:50%;flex-shrink:0;">
                            <i class="fas fa-user" style="font-size:13px;"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-sm text-gray-800">{{ $appUser->name ?? 'Unnamed User' }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ substr($appUser->uuid, 0, 16) }}…</div>
                        </div>
                    </div>
                </td>
                <td class="text-sm text-gray-600">{{ $appUser->email ?? '—' }}</td>
                <td class="text-xs font-mono text-gray-400">{{ substr($appUser->device_id, 0, 20) }}…</td>
                <td>
                    <span class="badge-info">{{ $appUser->payments->count() }} payment{{ $appUser->payments->count() !== 1 ? 's' : '' }}</span>
                </td>
                <td class="text-sm text-gray-500">{{ $appUser->created_at->format('d M Y') }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex justify-end">
                        <a href="{{ route('admin.app_users.show', $appUser->id) }}" class="text-gray-400 hover:text-blue-600 transition-colors" title="View">
                            <i class="fas fa-eye text-sm"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No app users yet</p>
                    <p class="text-gray-400 text-sm mt-1">Users appear here when they make their first payment</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($appUsers->hasPages())
<div class="mt-4">{{ $appUsers->links() }}</div>
@endif

<div class="mt-3 text-xs text-gray-400">
    Showing {{ $appUsers->firstItem() ?? 0 }}–{{ $appUsers->lastItem() ?? 0 }} of {{ $appUsers->total() }} results
</div>
@endsection
