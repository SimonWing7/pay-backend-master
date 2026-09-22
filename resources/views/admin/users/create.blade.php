@extends('admin.layout')

@section('title', 'Invite Admin')
@section('page-title', 'Invite Admin')
@section('page-subtitle', 'Give someone else access to this dashboard')

@section('topbar-actions')
    <a href="{{ route('admin.users.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="card p-6">
        <p class="text-xs text-gray-400 mb-5">They'll get the same full admin access you have — there's no restricted role yet. You'll get a link after this to share with them yourself.</p>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf

            <div class="mb-5">
                <label for="name" class="form-label">Full Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="form-input @error('name') border-red-400 @enderror">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="email" class="form-label">Email <span class="text-red-400">*</span></label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                    class="form-input @error('email') border-red-400 @enderror">
                @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="btn-secondary flex-1 justify-center">Cancel</a>
                <button type="submit" class="btn-primary flex-1 justify-center">
                    <i class="fas fa-user-plus"></i> Create Invite
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
