<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\BillingStatement;
use App\Models\MerchantBillingCard;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(protected BillingService $billing)
    {
    }

    public function index(Request $request): View
    {
        $merchant = $request->user();

        $card = $merchant->activeBillingCard;
        $pendingCard = $merchant->billingCards()
            ->where('status', MerchantBillingCard::STATUS_PENDING)
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->first();
        $upcoming = $this->billing->upcomingFor($merchant);
        $statements = $merchant->billingStatements()
            ->where('status', '!=', BillingStatement::STATUS_VOID)
            ->orderByDesc('period_start')
            ->limit(24)
            ->get();

        return view('merchant.billing.index', compact('merchant', 'card', 'pendingCard', 'upcoming', 'statements'));
    }

    public function startCard(Request $request): RedirectResponse
    {
        $request->validate([
            'consent' => 'accepted',
        ], [
            'consent.accepted' => 'Please tick the box to authorise Edfundo to charge this card.',
        ]);

        // Registering from the onboarding journey: come back to it afterwards.
        if ($request->input('return_to') === 'onboarding') {
            $request->session()->put('billing_return', 'onboarding');
            // One tick covers the fees agreement and the card authorisation.
            app(\App\Services\OnboardingService::class)->recordTerms(
                $request->user(),
                (string) $request->ip(),
                $request->attributes->get('merchant_member')?->name ?? $request->user()->name
            );
        } else {
            $request->session()->forget('billing_return');
        }

        $result = $this->billing->startCardSetup($request->user(), (string) $request->ip());

        if (!$result['success']) {
            if ($request->session()->pull('billing_return') === 'onboarding') {
                return redirect()->route('merchant.onboarding.show', 'fees')->with('error', $result['error']);
            }

            return redirect()->route('merchant.billing.index')->with('error', $result['error']);
        }

        return redirect()->away($result['url']);
    }

    /** Where Telr sends the merchant back to, whatever the outcome. */
    public function cardReturn(Request $request, string $cart): RedirectResponse
    {
        $card = MerchantBillingCard::where('merchant_id', $request->user()->id)
            ->where('setup_cartid', $cart)
            ->firstOrFail();

        return $this->finishCardSetup($card);
    }

    public function cardCheck(Request $request, int $card): RedirectResponse
    {
        $card = MerchantBillingCard::where('merchant_id', $request->user()->id)->findOrFail($card);

        return $this->finishCardSetup($card);
    }

    private function finishCardSetup(MerchantBillingCard $card): RedirectResponse
    {
        $outcome = $this->billing->completeCardSetup($card);

        $flash = match ($outcome) {
            'activated'      => ['success', 'Your card has been registered. The AED 1.00 verification charge will be returned to you.'],
            'already_active' => ['success', 'Your card is registered.'],
            'pending'        => ['info', 'Your card registration is still being processed. Check again in a minute.'],
            'declined'       => ['error', 'The card could not be registered. Nothing was charged. Please try again or use a different card.'],
            default          => ['error', 'We could not confirm the card registration just now. Please check again in a minute.'],
        };

        if (request()->session()->pull('billing_return') === 'onboarding') {
            return redirect()->route('merchant.onboarding.show', 'fees')->with($flash[0], $flash[1]);
        }

        return redirect()->route('merchant.billing.index')->with($flash[0], $flash[1]);
    }

    public function statement(Request $request, string $number): View
    {
        $statement = BillingStatement::where('merchant_id', $request->user()->id)
            ->where('number', $number)
            ->firstOrFail();

        return view('merchant.billing.statement', ['statement' => $statement, 'merchant' => $request->user()]);
    }
}
