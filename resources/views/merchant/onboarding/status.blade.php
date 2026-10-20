@extends('merchant.onboarding.layout')

@section('title', 'Under review')

@section('content')
<div class="text-center py-4">
    <div class="dot mx-auto mb-4" style="width:56px;height:56px;background:#ede9fe;color:#3d01bd;font-size:22px;"><i class="fas fa-hourglass-half"></i></div>
    <h1 class="text-xl font-extrabold mb-2" style="color:#000026;">Thanks, we have your details</h1>
    <p class="text-sm mb-6" style="color:#6b7280;line-height:1.6;">Edfundo is reviewing your account
        @if($onboarding->submitted_at) (sent {{ $onboarding->submitted_at->copy()->setTimezone('Asia/Dubai')->format('j F Y, H:i') }})@endif.
        We'll email <strong>{{ $onboarding->merchant->email }}</strong> as soon as you're approved.</p>
    <p class="text-sm mb-6" style="color:#6b7280;line-height:1.6;">In the meantime you can create products and add your payers. Payment links go live once you're approved.</p>
    <a href="{{ route('merchant.dashboard') }}" class="btn-gradient">Go to my dashboard</a>
</div>
@endsection
