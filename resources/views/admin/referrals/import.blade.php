@extends('admin.layout')

@section('title', 'Import Referral Signups')
@section('page-title', 'Import Referral Signups')
@section('page-subtitle', 'Upload the main Edfundo app\'s user export to match new signups against merchant payments')

@section('topbar-actions')
    <a href="{{ route('admin.referrals.index') }}" class="btn-secondary">
        <i class="fas fa-arrow-left mr-2"></i>Back to Referrals
    </a>
@endsection

@section('content')

<div class="card p-6 max-w-2xl">
    <p class="text-sm text-gray-600 mb-6">
        Upload the daily user export from the main Edfundo app's admin panel (columns including
        <span class="font-mono text-xs">Email</span>, <span class="font-mono text-xs">User Id</span>,
        <span class="font-mono text-xs">SignUp Date</span> and <span class="font-mono text-xs">Subscription Status</span>).
        Each row's email is matched against Edfundo Pay's own paid invoices to attribute the referral to the
        right merchant — only rows with <span class="font-mono text-xs">Subscription Status = Active</span> count
        toward commission. Safe to re-upload the same or overlapping export; existing referrals are updated, not duplicated.
    </p>

    @if($errors->any())
        <div class="alert-error mb-5">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.referrals.import.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-5">
            <label for="export" class="form-label">Export file (CSV)</label>
            <input type="file" name="export" id="export" required accept=".csv,.tsv,.txt"
                class="form-input @error('export') border-red-400 @enderror">
            @error('export')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-primary">
            <i class="fas fa-upload"></i> Upload and Process
        </button>
    </form>
</div>

@endsection
