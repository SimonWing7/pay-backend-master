<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingStatement;
use App\Models\Merchant;
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
        $statements = BillingStatement::with(['merchant', 'attemptsLog'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('merchant_id'), fn ($q) => $q->where('merchant_id', $request->get('merchant_id')))
            ->orderByDesc('period_start')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $needsAttention = BillingStatement::whereIn('status', [
            BillingStatement::STATUS_FAILED,
            BillingStatement::STATUS_AWAITING_CARD,
            BillingStatement::STATUS_NEEDS_REVIEW,
        ])->count();

        $merchants = Merchant::orderBy('name')->get(['id', 'name']);

        return view('admin.billing.index', compact('statements', 'merchants', 'needsAttention'));
    }

    public function retry(Request $request, int $id): RedirectResponse
    {
        $statement = BillingStatement::findOrFail($id);

        // A statement under review may already have been charged — retrying
        // blind could bill the merchant twice, so the admin has to confirm.
        if ($statement->status === BillingStatement::STATUS_NEEDS_REVIEW) {
            $request->validate(
                ['confirmed' => 'accepted'],
                ['confirmed.accepted' => 'Tick the box to confirm in Telr that no charge was made before retrying a statement under review.']
            );
        }

        $result = $this->billing->retryNow($statement);

        $message = match ($result) {
            'paid'            => "{$statement->number} charged successfully.",
            'retry_scheduled' => "{$statement->number}: the card was declined; a retry has been scheduled.",
            'failed'          => "{$statement->number}: the card was declined.",
            'awaiting_card'   => "{$statement->number}: the merchant has no card on file.",
            'needs_review'    => "{$statement->number}: Telr gave no clear answer. Check the Telr dashboard.",
            default           => "{$statement->number} could not be retried in its current state.",
        };

        return redirect()->back()->with($result === 'paid' ? 'success' : 'error', $message);
    }

    public function markPaid(Request $request, int $id): RedirectResponse
    {
        $request->validate(['note' => 'required|string|max:255']);

        $statement = BillingStatement::findOrFail($id);

        if (in_array($statement->status, [BillingStatement::STATUS_PAID, BillingStatement::STATUS_VOID, BillingStatement::STATUS_CHARGING], true)) {
            return redirect()->back()->with('error', "{$statement->number} can't be marked paid in its current state.");
        }

        $this->billing->markPaidManually($statement, 'Marked paid by ' . $request->user()->name . ': ' . $request->input('note'));

        return redirect()->back()->with('success', "{$statement->number} marked as paid.");
    }

    public function void(Request $request, int $id): RedirectResponse
    {
        $statement = BillingStatement::findOrFail($id);

        if (!$this->billing->void($statement)) {
            return redirect()->back()->with('error', "{$statement->number} can't be voided in its current state.");
        }

        return redirect()->back()->with('success', "{$statement->number} voided.");
    }
}
