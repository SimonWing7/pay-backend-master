@extends('admin.layout')

@section('title', 'Two-Factor Authentication')
@section('page-title', 'Two-Factor Authentication')
@section('page-subtitle', 'Secure your admin account with an authenticator app')

@section('content')

@if(session('recovery_codes'))
<div class="card p-6 mb-6" style="border: 2px solid #d97706; background: #fffbeb;">
    <h3 class="text-sm font-bold text-amber-800 mb-2"><i class="fas fa-triangle-exclamation mr-2"></i>Save your recovery codes now</h3>
    <p class="text-sm text-amber-700 mb-4">Each code can be used once to sign in if you lose access to your authenticator app. They will not be shown again after you leave this page.</p>
    <div class="grid grid-cols-2 gap-2 bg-white rounded-lg p-4 font-mono text-sm">
        @foreach(session('recovery_codes') as $code)
            <div>{{ $code }}</div>
        @endforeach
    </div>
</div>
@endif

<div class="card p-6 max-w-2xl">
    @if($admin->hasTwoFactorEnabled())
        <div class="flex items-center gap-3 mb-4">
            <span class="badge-success"><i class="fas fa-check-circle mr-1"></i>Enabled</span>
            <p class="text-sm text-gray-500">Two-factor authentication is protecting this account.</p>
        </div>

        <div class="flex gap-3 mt-6">
            <form method="POST" action="{{ route('admin.two-factor.recovery-codes') }}" onsubmit="return confirm('This invalidates your existing recovery codes. Continue?');">
                @csrf
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-arrows-rotate"></i> Regenerate recovery codes
                </button>
            </form>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Disable two-factor authentication</h4>
            <form method="POST" action="{{ route('admin.two-factor.disable') }}" class="flex items-end gap-3" onsubmit="return confirm('Are you sure you want to disable two-factor authentication?');">
                @csrf
                <div class="flex-1 max-w-xs">
                    <label for="password" class="form-label">Confirm your password</label>
                    <input type="password" name="password" id="password" required class="form-input @error('password') border-red-400 @enderror">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-secondary" style="border-color: #fecaca; color: #dc2626;">
                    <i class="fas fa-ban"></i> Disable
                </button>
            </form>
        </div>
    @else
        <div class="flex items-center gap-3 mb-4">
            <span class="badge-warning"><i class="fas fa-exclamation-circle mr-1"></i>Not enabled</span>
            <p class="text-sm text-gray-500">Your account is only protected by a password.</p>
        </div>
        <a href="{{ route('admin.two-factor.setup') }}" class="btn-primary">
            <i class="fas fa-shield-halved"></i> Enable Two-Factor Authentication
        </a>
    @endif
</div>

@endsection
