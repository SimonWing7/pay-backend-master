<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/** The new-merchant set-up journey: one question screen at a time, resumable. */
class OnboardingController extends Controller
{
    public function __construct(protected OnboardingService $onboarding)
    {
    }

    /** Send the merchant to wherever they left off. */
    public function index(Request $request): RedirectResponse|View
    {
        $merchant = $request->user();

        if ($merchant->isOnboarded()) {
            return redirect()->route('merchant.dashboard');
        }

        $onboarding = $this->onboarding->forMerchant($merchant);

        if (!$this->onboarding->isEditable($merchant)) {
            return view('merchant.onboarding.status', ['onboarding' => $onboarding]);
        }

        $step = in_array($onboarding->current_step, OnboardingService::STEPS, true) ? $onboarding->current_step : 'welcome';

        return redirect()->route('merchant.onboarding.show', $step);
    }

    public function show(Request $request, string $step): RedirectResponse|View
    {
        $merchant = $request->user();

        if ($merchant->isOnboarded()) {
            return redirect()->route('merchant.dashboard');
        }

        if (!$this->onboarding->isEditable($merchant)) {
            return redirect()->route('merchant.onboarding.index');
        }

        $onboarding = $this->onboarding->forMerchant($merchant);

        // Opening a screen is where "you're on step X" is remembered, so a
        // merchant who closes the tab comes back to the same place.
        $this->onboarding->moveTo($onboarding, $step);

        $data = ['step' => $step, 'onboarding' => $onboarding->fresh(), 'answers' => $onboarding->step($step)];

        return view($step === 'review' ? 'merchant.onboarding.review' : 'merchant.onboarding.step', $data);
    }

    public function save(Request $request, string $step): RedirectResponse
    {
        $merchant = $request->user();

        if (!$this->onboarding->isEditable($merchant)) {
            return redirect()->route('merchant.onboarding.index');
        }

        $onboarding = $this->onboarding->forMerchant($merchant);
        $action = $request->input('action', 'next');

        if ($step !== 'welcome' && $step !== 'review') {
            // Moving forward needs a complete, valid screen. Going back or
            // saving for later keeps whatever was typed and only insists on
            // the formats of what was filled in.
            $strict = $action === 'next';

            $input = $this->onboarding->normalise($step, $request->all());
            $validator = Validator::make(
                $input + $request->allFiles(),
                $this->onboarding->rules($step, !$strict),
                $this->onboarding->messages()
            );

            if ($validator->fails()) {
                if ($action === 'back') {
                    return redirect()->route('merchant.onboarding.show', $this->onboarding->previousStep($step));
                }

                return redirect()->back()->withErrors($validator)->withInput($request->except(['logo', 'signatory_id', 'bank_letter']));
            }

            $this->onboarding->saveStep(
                $onboarding,
                $step,
                $validator->validated() + ['terms' => $request->boolean('terms')],
                (string) $request->ip(),
                $request->attributes->get('merchant_member')?->name ?? $merchant->name
            );
        }

        // The fees screen can't be passed without the card once billing is live.
        if ($step === 'fees' && $action === 'next' && !$this->onboarding->isStepComplete($onboarding->fresh(), 'fees')) {
            return redirect()->back()->withErrors([
                'card' => 'Please tick the box and press "Agree & add card" to register your card. You can continue once it is added.',
            ]);
        }

        return match ($action) {
            'later' => $this->saveForLater($onboarding, $step),
            'back' => redirect()->route('merchant.onboarding.show', $this->onboarding->previousStep($step)),
            default => redirect()->route('merchant.onboarding.show', $this->onboarding->nextStep($step)),
        };
    }

    public function submit(Request $request): RedirectResponse
    {
        $error = $this->onboarding->submit($request->user());

        if ($error) {
            return redirect()->route('merchant.onboarding.show', 'review')->with('error', $error);
        }

        return redirect()->route('merchant.onboarding.index');
    }

    private function saveForLater($onboarding, string $step): RedirectResponse
    {
        $this->onboarding->moveTo($onboarding, $step);

        return redirect()->route('merchant.dashboard')
            ->with('success', 'Progress saved. Pick up where you left off any time from the banner below.');
    }
}
