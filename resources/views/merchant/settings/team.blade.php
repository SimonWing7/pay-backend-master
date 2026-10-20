@extends('merchant.layout')

@section('title', 'Settings — Team')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your account and business details')

@section('content')

@include('merchant.settings._tabs')

<div class="grid gap-6" style="grid-template-columns: minmax(0, 1fr);">

    {{-- Invite --}}
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Invite someone</h3>
        <p class="text-sm text-gray-500 mb-5">They get an email with a link to choose their own password. You never see or share it.</p>

        <form method="POST" action="{{ route('merchant.team.invite') }}">
            @csrf
            <div class="grid gap-4 md:grid-cols-3 mb-4">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required maxlength="255"
                        class="form-input @error('name', 'invite') border-red-400 @enderror">
                    @error('name', 'invite')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required maxlength="255"
                        class="form-input @error('email', 'invite') border-red-400 @enderror">
                    @error('email', 'invite')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-input">
                        @foreach($roles as $key => $role)
                            <option value="{{ $key }}" {{ old('role', 'staff') === $key ? 'selected' : '' }}>{{ $role['label'] }}</option>
                        @endforeach
                    </select>
                    @error('role', 'invite')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-2 md:grid-cols-3 mb-5">
                @foreach($roles as $key => $role)
                <div class="text-xs text-gray-500"><strong class="text-gray-700">{{ $role['label'] }}</strong><br>{{ $role['description'] }}</div>
                @endforeach
            </div>

            <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Send invitation</button>
        </form>
    </div>

    {{-- Current team --}}
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">People with access</h3>
        </div>
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Person</th>
                    <th class="text-left">Role</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Last sign-in</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="font-semibold text-sm text-gray-800">{{ $merchant->name }}</div>
                        <div class="text-xs text-gray-400">{{ $merchant->email }}</div>
                    </td>
                    <td><span class="badge-muted">Owner</span> <span class="text-xs text-gray-400">account login</span></td>
                    <td><span class="badge-success">Active</span></td>
                    <td class="text-sm text-gray-400">—</td>
                    <td></td>
                </tr>
                @foreach($members as $member)
                <tr>
                    <td>
                        <div class="font-semibold text-sm text-gray-800">{{ $member->name }}</div>
                        <div class="text-xs text-gray-400">{{ $member->email }}</div>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('merchant.team.role', $member->id) }}" class="inline">
                            @csrf
                            @method('PUT')
                            <select name="role" class="form-input text-sm py-1" style="width:auto;" onchange="this.form.submit()">
                                @foreach($roles as $key => $role)
                                    <option value="{{ $key }}" {{ $member->role === $key ? 'selected' : '' }}>{{ $role['label'] }}</option>
                                @endforeach
                            </select>
                        </form>
                    </td>
                    <td>
                        @if($member->isPending())
                            <span class="badge-warning">Invited</span>
                            <div class="text-xs text-gray-400">{{ $member->invited_at?->copy()->setTimezone('Asia/Dubai')->format('j M, H:i') }}</div>
                        @else
                            <span class="badge-success">Active</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $member->last_login_at ? $member->last_login_at->copy()->setTimezone('Asia/Dubai')->format('j M Y, H:i') : '—' }}</td>
                    <td class="text-right">
                        @if($member->isPending())
                        <form method="POST" action="{{ route('merchant.team.resend', $member->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-blue-600 transition-colors text-xs font-medium mr-3">
                                <i class="fas fa-paper-plane"></i> Resend invite
                            </button>
                        </form>
                        @endif
                        <form method="POST" action="{{ route('merchant.team.destroy', $member->id) }}" class="inline"
                            onsubmit="return confirm('Remove {{ addslashes($member->name) }}? They will lose access straight away.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-600 transition-colors text-xs font-medium">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($members->isEmpty())
        <div class="px-6 py-8 text-center text-sm text-gray-400">No one else has access yet. Invite a colleague above.</div>
        @endif
    </div>

</div>
@endsection
