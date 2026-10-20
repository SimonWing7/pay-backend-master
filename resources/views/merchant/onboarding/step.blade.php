@extends('merchant.onboarding.layout')

@section('title', \App\Services\OnboardingService::TITLES[$step])

@section('content')
@includeIf('merchant.onboarding.steps.' . $step . '-before')
<form method="POST" action="{{ route('merchant.onboarding.save', $step) }}" enctype="multipart/form-data" novalidate>
    @csrf
    @include('merchant.onboarding.steps.' . $step)
    @include('merchant.onboarding._footer', ['step' => $step, 'nextLabel' => $step === 'welcome' ? "Let's start" : null])
</form>
@endsection
