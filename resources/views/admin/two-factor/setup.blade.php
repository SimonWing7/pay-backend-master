@extends('admin.layout')

@section('title', 'Set Up Two-Factor Authentication')
@section('page-title', 'Set Up Two-Factor Authentication')
@section('page-subtitle', 'Scan the code with your authenticator app')

@section('content')

<div class="card p-6 max-w-2xl">
    <div class="mb-6">
        <p class="text-sm text-gray-600 mb-4">
            Scan this QR code with an authenticator app (Google Authenticator, 1Password, Authy, etc.),
            then enter the 6-digit code it generates to confirm setup.
        </p>
        <div class="flex justify-center bg-gray-50 rounded-lg p-6 mb-4">
            <img src="{{ $qr }}" alt="Two-factor authentication QR code" style="width: 200px; height: 200px;">
        </div>
        <p class="text-xs text-gray-400 text-center">Can't scan it? Enter this key manually: <span class="font-mono text-gray-600">{{ $secret }}</span></p>
    </div>

    @if($errors->any())
        <div class="alert-error mb-5">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.two-factor.confirm') }}" class="max-w-xs">
        @csrf
        <label for="code" class="form-label">Enter the 6-digit code</label>
        <input type="text" name="code" id="code" required autofocus autocomplete="one-time-code"
            inputmode="numeric" maxlength="6" class="form-input mb-4" placeholder="000000">
        <div class="flex gap-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-check"></i> Confirm and Enable
            </button>
            <a href="{{ route('admin.two-factor.index') }}" class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>

@endsection
