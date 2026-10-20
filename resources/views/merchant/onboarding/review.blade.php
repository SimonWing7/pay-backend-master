@extends('merchant.onboarding.layout')

@section('title', 'Review & submit')

@section('content')
@php
    $svc = app(\App\Services\OnboardingService::class);
    $missing = $svc->incompleteSteps($onboarding);
    $b = $onboarding->step('business'); $r = $onboarding->step('registration'); $k = $onboarding->step('bank'); $c = $onboarding->step('contact');
    $rows = [
        'business' => [['Business name', $b['trading_name'] ?? null], ['Support email', $b['support_email'] ?? null], ['Support phone', $b['support_phone'] ?? null], ['Website', $b['website'] ?? null]],
        'registration' => [['Trade licence number', $r['trade_license_number'] ?? null], ['Address', $r['address'] ?? null], ['City', $r['city'] ?? null]],
        'bank' => [['Account holder', $k['account_holder_name'] ?? null], ['Bank', $k['bank_name'] ?? null], ['IBAN', $k['iban'] ?? null], ['Account number', $k['account_number'] ?? null], ['SWIFT code', $k['swift_code'] ?? null]],
        'contact' => [['Name', $c['signatory_name'] ?? null], ['Role', $c['signatory_role'] ?? null], ['Mobile', $c['signatory_mobile'] ?? null], ['Email', $c['signatory_email'] ?? null]],
        'fees' => [['Agreement', $onboarding->terms_accepted_at ? 'Accepted ' . $onboarding->terms_accepted_at->copy()->setTimezone('Asia/Dubai')->format('j M Y') : null]],
    ];
@endphp

<h1 class="text-xl font-extrabold mb-1" style="color:#000026;">Check your details</h1>
<p class="text-sm mb-6" style="color:#6b7280;">Have a quick look, then send everything to Edfundo for review.</p>

@foreach($rows as $key => $items)
<div class="mb-5 rounded-xl p-4" style="border:1px solid {{ in_array($key, $missing, true) ? '#fcd34d' : '#f3f4f6' }};background:{{ in_array($key, $missing, true) ? '#fffbeb' : '#fff' }};">
    <div class="flex items-center justify-between mb-2">
        <p class="text-sm font-bold" style="color:#111827;">{{ \App\Services\OnboardingService::TITLES[$key] }}</p>
        <a href="{{ route('merchant.onboarding.show', $key) }}" class="text-xs font-semibold" style="color:#3d01bd;">{{ in_array($key, $missing, true) ? 'Finish this step' : 'Edit' }}</a>
    </div>
    @if(in_array($key, $missing, true))
        <p class="text-xs" style="color:#b45309;"><i class="fas fa-exclamation-triangle"></i>
            @if($key === 'fees' && $svc->cardRequired() && !$onboarding->merchant->activeBillingCard)
                Your card hasn't been added yet. Add it to agree to the fees.
            @else
                Some required details are missing here.
            @endif
        </p>
    @endif
    <dl class="text-sm">
        @foreach($items as [$label, $value])
        <div class="flex justify-between gap-4 py-1">
            <dt style="color:#9ca3af;">{{ $label }}</dt>
            <dd class="text-right" style="color:#111827;word-break:break-all;">{{ $value ?: '—' }}</dd>
        </div>
        @endforeach
    </dl>
</div>
@endforeach

<form method="POST" action="{{ route('merchant.onboarding.submit') }}">
    @csrf
    <div class="flex flex-wrap items-center justify-between gap-3 mt-6 pt-6" style="border-top:1px solid #f3f4f6;">
        <div class="flex items-center gap-1">
            <a href="{{ route('merchant.onboarding.show', 'fees') }}" class="btn-ghost">&larr; Back</a>
            <a href="{{ route('merchant.dashboard') }}" class="btn-link">Finish later</a>
        </div>
        <button type="submit" class="btn-gradient" {{ $missing ? 'disabled style=opacity:.5;cursor:not-allowed;' : '' }}>Submit for review</button>
    </div>
</form>
@endsection
