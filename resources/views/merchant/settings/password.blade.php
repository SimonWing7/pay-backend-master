@extends('merchant.layout')

@section('title', 'Settings — Change Password')
@section('page-title', 'Settings')
@section('page-subtitle', 'Manage your account and business details')

@section('content')

@include('merchant.settings._tabs')

<div class="max-w-lg">
    <div class="card p-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-5">Change Password</h3>

        <form method="POST" action="{{ route('merchant.settings.password.post') }}">
            @csrf

            <div class="mb-5">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" name="current_password" id="current_password" required
                    class="form-input @error('current_password') border-red-400 @enderror">
                @error('current_password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" name="new_password" id="new_password" required minlength="8"
                    class="form-input @error('new_password') border-red-400 @enderror">
                <p class="text-xs text-gray-400 mt-1">Must be at least 8 characters</p>
                @error('new_password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" id="new_password_confirmation" required minlength="8"
                    class="form-input @error('new_password_confirmation') border-red-400 @enderror">
                @error('new_password_confirmation')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('merchant.dashboard') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-lock"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
