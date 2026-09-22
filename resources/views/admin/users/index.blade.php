@extends('admin.layout')

@section('title', 'Admin Users')
@section('page-title', 'Admin Users')
@section('page-subtitle', 'Who has access to this dashboard')

@section('topbar-actions')
    <a href="{{ route('admin.users.create') }}" class="btn-primary">
        <i class="fas fa-user-plus"></i> Invite Admin
    </a>
@endsection

@section('content')

@if(session('invite_link'))
<div class="card p-6 mb-6" style="background:#f0f4ff;border:1px solid #c7d2fe;">
    <p class="text-sm font-semibold text-gray-800 mb-2"><i class="fas fa-link mr-1"></i> Invite link ready — copy and share this with them (email, Slack, WhatsApp, whatever's easiest):</p>
    <div class="flex items-center gap-3">
        <code id="inviteLink" class="flex-1 px-4 py-3 rounded-lg text-sm font-mono break-all" style="background:#ffffff;border:1px solid #c7d2fe;color:#3d01bd;">{{ session('invite_link') }}</code>
        <button type="button" onclick="copyInviteLink()" id="copyInviteBtn" class="btn-secondary flex-shrink-0">
            <i class="fas fa-copy"></i> Copy
        </button>
    </div>
    <p class="text-xs text-gray-400 mt-2">This link only works once — it stops working the moment they set their password.</p>
</div>
@endif

<div class="card overflow-hidden">
    <table class="data-table w-full">
        <thead>
            <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Email</th>
                <th class="text-left">Status</th>
                <th class="text-left">2FA</th>
                <th class="text-left">Invited By</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($admins as $admin)
            <tr>
                <td class="text-sm font-semibold text-gray-800">
                    {{ $admin->name }}
                    @if($admin->id === auth('admin')->id())
                        <span class="text-xs text-gray-400">(you)</span>
                    @endif
                </td>
                <td class="text-sm text-gray-600">{{ $admin->email }}</td>
                <td>
                    @if($admin->isPendingInvite())
                        <span class="badge-warning">Invite Pending</span>
                    @else
                        <span class="badge-success">Active</span>
                    @endif
                </td>
                <td>
                    @if($admin->hasTwoFactorEnabled())
                        <span class="badge-success"><i class="fas fa-check-circle mr-1"></i>Enabled</span>
                    @else
                        <span class="text-xs text-gray-400">Not set up</span>
                    @endif
                </td>
                <td class="text-sm text-gray-500">{{ $admin->invitedBy?->name ?? '—' }}</td>
                <td onclick="event.stopPropagation();">
                    <div class="flex justify-end">
                        @if($admin->id !== auth('admin')->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $admin->id) }}"
                            onsubmit="return confirm('Remove {{ $admin->name }}\'s access? This can\'t be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors" title="Remove">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="stat-icon mx-auto mb-4" style="width:52px;height:52px;font-size:20px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <p class="text-gray-500 font-medium">No admin users yet</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection

@push('scripts')
<script>
function copyInviteLink() {
    const link = document.getElementById('inviteLink').innerText;
    navigator.clipboard.writeText(link).then(function () {
        const btn = document.getElementById('copyInviteBtn');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copied';
        setTimeout(() => { btn.innerHTML = original; }, 2000);
    });
}
</script>
@endpush
