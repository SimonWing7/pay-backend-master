@extends('admin.layout')

@section('title', 'Add Referral Manually')
@section('page-title', 'Add Referral Manually')
@section('page-subtitle', 'For cases automated matching can\'t catch — different email/mobile at signup, or a direct signup with no link click')

@section('topbar-actions')
    <a href="{{ route('admin.referrals.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Referrals
    </a>
@endsection

@section('content')

<div class="card p-6 max-w-2xl">

    @if($errors->any())
        <div class="alert-error mb-5">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.referrals.store') }}">
        @csrf

        <div class="mb-5">
            <label for="merchant_id" class="form-label">Merchant</label>
            <select name="merchant_id" id="merchant_id" required class="form-input @error('merchant_id') border-red-400 @enderror">
                <option value="">Select merchant&hellip;</option>
                @foreach($merchants as $merchant)
                    <option value="{{ $merchant->id }}" {{ old('merchant_id') == $merchant->id ? 'selected' : '' }}>{{ $merchant->name }}</option>
                @endforeach
            </select>
            @error('merchant_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="edfundo_user_email" class="form-label">Edfundo app user email</label>
            <input type="email" name="edfundo_user_email" id="edfundo_user_email" value="{{ old('edfundo_user_email') }}" required
                class="form-input @error('edfundo_user_email') border-red-400 @enderror" placeholder="parent@example.com">
            @error('edfundo_user_email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="edfundo_user_id" class="form-label">Edfundo app User Id</label>
            <input type="text" name="edfundo_user_id" id="edfundo_user_id" value="{{ old('edfundo_user_id') }}" required
                class="form-input @error('edfundo_user_id') border-red-400 @enderror" placeholder="e.g. 85er2392-d5f0-17a3-a5f6-f64b6c6c6696">
            <p class="text-xs text-gray-400 mt-1">Look this up in the main Edfundo app's admin panel — it's the "User Id" column on their user export.</p>
            @error('edfundo_user_id')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="registered_at" class="form-label">Signup date</label>
            <input type="date" name="registered_at" id="registered_at" value="{{ old('registered_at', now()->format('Y-m-d')) }}"
                class="form-input @error('registered_at') border-red-400 @enderror">
            @error('registered_at')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-3">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                <input type="checkbox" name="is_subscribed" value="1" id="is_subscribed" {{ old('is_subscribed') ? 'checked' : '' }}
                    onchange="document.getElementById('subscribed_at_field').style.display = this.checked ? 'block' : 'none';">
                Already on a paid ("Active") subscription — mark commission as earned
            </label>
        </div>

        <div id="subscribed_at_field" class="mb-5" style="display: {{ old('is_subscribed') ? 'block' : 'none' }};">
            <label for="subscribed_at" class="form-label">Subscription start date</label>
            <input type="date" name="subscribed_at" id="subscribed_at" value="{{ old('subscribed_at', now()->format('Y-m-d')) }}"
                class="form-input @error('subscribed_at') border-red-400 @enderror">
            @error('subscribed_at')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" id="notes" rows="3" class="form-input @error('notes') border-red-400 @enderror"
                placeholder="e.g. Parent reported via support — paid via J20 using dad's email, signed up to Edfundo under mum's email.">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn-primary">
            <i class="fas fa-plus"></i> Create Referral
        </button>
    </form>
</div>

@endsection
