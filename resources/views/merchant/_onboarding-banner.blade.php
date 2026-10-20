@php
    $bannerMerchant = auth('merchants')->user();
    $showBanner = $bannerMerchant
        && !$bannerMerchant->isOnboarded()
        && ($teamRole ?? 'owner') === 'owner';
@endphp
@if($showBanner)
    @php
        $bannerSvc = app(\App\Services\OnboardingService::class);
        $bannerOnboarding = $bannerSvc->forMerchant($bannerMerchant);
        $bannerProgress = $bannerSvc->progress($bannerOnboarding);
        $bannerStatus = $bannerMerchant->onboarding_status;
    @endphp
    @if($bannerStatus === 'submitted')
        <div class="mb-5 flex items-center gap-3 rounded-lg px-4 py-3 text-sm" style="background:#f5f3ff;color:#4c1d95;border:1px solid #ddd6fe;">
            <i class="fas fa-hourglass-half"></i>
            <span class="flex-1">Your details are with Edfundo for review. You can set up products and payers now; payment links go live once you're approved.</span>
        </div>
    @else
        <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:{{ $bannerStatus === 'changes_requested' ? '#fffbeb' : '#f5f3ff' }};color:{{ $bannerStatus === 'changes_requested' ? '#92400e' : '#4c1d95' }};border:1px solid {{ $bannerStatus === 'changes_requested' ? '#fcd34d' : '#ddd6fe' }};">
            <div class="flex flex-wrap items-center gap-3">
                <i class="fas fa-rocket"></i>
                <span class="flex-1">
                    @if($bannerStatus === 'changes_requested')
                        <strong>Edfundo asked for a few changes</strong> before approving your account.
                    @else
                        <strong>Finish setting up your account</strong> to start collecting payments. {{ $bannerProgress['done'] }} of {{ $bannerProgress['total'] }} steps done.
                    @endif
                </span>
                <a href="{{ route('merchant.onboarding.index') }}" class="btn-primary" style="padding:6px 14px;font-size:13px;">
                    {{ $bannerProgress['done'] > 0 || $bannerStatus === 'changes_requested' ? 'Continue' : 'Start' }}
                </a>
            </div>
            @if($bannerStatus === 'changes_requested' && $bannerOnboarding->review_note)
                <p class="mt-2 text-sm" style="white-space:pre-line;">{{ $bannerOnboarding->review_note }}</p>
            @endif
        </div>
    @endif
@endif
