<?php

namespace App\Services;

use App\Mail\BillingChargeResultMail;
use App\Mail\BillingStatementMail;
use App\Models\AppUserPayment;
use App\Models\BillingChargeAttempt;
use App\Models\BillingStatement;
use App\Models\Merchant;
use App\Models\MerchantBillingCard;
use App\Enums\PaymentStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Bills merchants the monthly fee Edfundo earns on their completed payments.
 *
 * A statement is issued on the 1st for the previous calendar month
 * (Asia/Dubai) and the merchant's registered card is charged on the 3rd,
 * with two retries if it's declined. Money-moving code is deliberately
 * defensive: one statement per merchant per month (unique index), a
 * statement is claimed before Telr is called, and anything ambiguous goes to
 * a human instead of being retried.
 */
class BillingService
{
    public function __construct(protected TelrService $telr)
    {
    }

    // ------------------------------------------------------------------
    // Amounts and periods
    // ------------------------------------------------------------------

    public function timezone(): string
    {
        return config('billing.timezone', 'Asia/Dubai');
    }

    /** @return array{0: Carbon, 1: Carbon} first and last instant of the Dubai calendar month containing $date */
    public function monthBounds(Carbon $date): array
    {
        $local = $date->copy()->setTimezone($this->timezone());

        return [$local->copy()->startOfMonth(), $local->copy()->endOfMonth()];
    }

    public function previousMonth(): Carbon
    {
        return now($this->timezone())->startOfMonth()->subMonthNoOverflow();
    }

    /** Completed payment volume (AED) for a merchant between two instants. */
    public function volumeFor(Merchant $merchant, Carbon $from, Carbon $to): float
    {
        // Stored timestamps are in the app timezone; Eloquent formats a Carbon
        // in its OWN timezone, so convert before querying.
        $appTz = config('app.timezone');

        $total = AppUserPayment::query()
            ->join('invoices', 'app_user_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.merchant_id', $merchant->id)
            ->where('app_user_payments.status', PaymentStatus::Complete->value)
            ->whereNull('app_user_payments.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->whereBetween('app_user_payments.created_at', [
                $from->copy()->setTimezone($appTz),
                $to->copy()->setTimezone($appTz),
            ])
            ->sum('invoices.total_fee');

        return round((float) $total, 2);
    }

    public function feeFor(float $volume, float $percentage): float
    {
        return round($volume * $percentage / 100, 2);
    }

    /**
     * What the merchant is on track to be invoiced for this month so far.
     *
     * @return array{period_label: string, volume: float, fee_percentage: float, fee: float, invoice_date: Carbon, charge_date: Carbon}
     */
    public function upcomingFor(Merchant $merchant): array
    {
        $now = now($this->timezone());
        [$start] = $this->monthBounds($now);

        $volume = $this->volumeFor($merchant, $start, $now);
        $percentage = (float) $merchant->fee_percentage;
        $nextMonth = $start->copy()->addMonthNoOverflow();

        return [
            'period_label'   => $start->format('F Y'),
            'volume'         => $volume,
            'fee_percentage' => $percentage,
            'fee'            => $this->feeFor($volume, $percentage),
            'invoice_date'   => $nextMonth->copy(),
            'charge_date'    => $nextMonth->copy()->day((int) config('billing.charge_day', 3)),
        ];
    }

    // ------------------------------------------------------------------
    // Statements
    // ------------------------------------------------------------------

    /**
     * The statement for one merchant and month. Safe to call repeatedly: an
     * existing statement is returned untouched (check wasRecentlyCreated to
     * know whether this call created it). Returns null when there's nothing
     * to bill.
     */
    public function generateStatement(Merchant $merchant, Carbon $periodMonth): ?BillingStatement
    {
        [$start, $end] = $this->monthBounds($periodMonth);

        // A statement for a month still in progress would lock in a partial
        // figure — and, since there's only ever one statement per merchant per
        // month, block the real one at month-end.
        if ($end->gte(now($this->timezone()))) {
            Log::warning('Billing: refused to issue a statement for a month that has not finished', [
                'merchant_id' => $merchant->id,
                'period'      => $start->format('Y-m'),
            ]);

            return null;
        }

        $existing = BillingStatement::where('merchant_id', $merchant->id)
            ->whereDate('period_start', $start->toDateString())
            ->first();

        if ($existing) {
            return $existing;
        }

        $volume = $this->volumeFor($merchant, $start, $end);
        $percentage = (float) $merchant->fee_percentage;
        $fee = $this->feeFor($volume, $percentage);

        if ($fee <= 0) {
            return null;
        }

        $dueLocal = $start->copy()->addMonthNoOverflow()->day((int) config('billing.charge_day', 3))->setTime(9, 0);
        $due = $dueLocal->copy()->setTimezone(config('app.timezone'));

        try {
            return BillingStatement::create([
                'merchant_id'     => $merchant->id,
                'number'          => sprintf('EP-%s-%04d', $start->format('Ym'), $merchant->id),
                'period_start'    => $start->toDateString(),
                'period_end'      => $end->toDateString(),
                'volume'          => $volume,
                'fee_percentage'  => $percentage,
                'fee_amount'      => $fee,
                'currency'        => config('billing.currency', 'AED'),
                'status'          => BillingStatement::STATUS_ISSUED,
                'issued_at'       => now(),
                'charge_due_at'   => $due,
                'next_attempt_at' => $due,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another process created it between our check and insert.
            return BillingStatement::where('merchant_id', $merchant->id)
                ->whereDate('period_start', $start->toDateString())
                ->first();
        }
    }

    public function sendStatementEmail(BillingStatement $statement): bool
    {
        $merchant = $statement->merchant;

        try {
            Mail::to($this->recipientFor($merchant))->send(new BillingStatementMail($statement, $merchant->activeBillingCard));
            $statement->forceFill(['emailed_at' => now()])->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Billing: statement email failed', ['statement' => $statement->number, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function recipientFor(Merchant $merchant): string
    {
        return $merchant->billingRecipient();
    }

    // ------------------------------------------------------------------
    // Charging
    // ------------------------------------------------------------------

    public function maxAttempts(): int
    {
        return count(config('billing.retry_after_days', [3, 7])) + 1;
    }

    /** @return \Illuminate\Support\Collection<int, BillingStatement> */
    public function dueStatements()
    {
        return BillingStatement::with('merchant')
            ->whereIn('status', [BillingStatement::STATUS_ISSUED, BillingStatement::STATUS_AWAITING_CARD])
            ->where('next_attempt_at', '<=', now())
            ->orderBy('id')
            ->get();
    }

    /**
     * A run that died between claiming a statement and recording Telr's answer
     * leaves it in "charging". Whether the card was charged is unknown, so move
     * it to review — never back to a state that would be charged again.
     */
    public function recoverStaleCharges(int $olderThanMinutes = 30): int
    {
        $stale = BillingStatement::where('status', BillingStatement::STATUS_CHARGING)
            ->where('updated_at', '<', now()->subMinutes($olderThanMinutes))
            ->get();

        foreach ($stale as $statement) {
            $statement->update([
                'status'          => BillingStatement::STATUS_NEEDS_REVIEW,
                'next_attempt_at' => null,
                'failure_message' => 'A charge was started but its result was never recorded. Check the Telr dashboard before retrying.',
            ]);
            Log::error('Billing: statement stuck mid-charge — check Telr before retrying', ['statement' => $statement->number]);
        }

        return $stale->count();
    }

    /**
     * Make one charge attempt for a statement.
     *
     * @return string skipped | awaiting_card | paid | retry_scheduled | failed | needs_review
     */
    public function charge(BillingStatement $statement, bool $ignoreSchedule = false): string
    {
        // 1. Claim it — committed before Telr is called.
        $claim = DB::transaction(function () use ($statement, $ignoreSchedule) {
            $locked = BillingStatement::with('merchant')->whereKey($statement->id)->lockForUpdate()->first();

            if (!$locked || !$locked->isOpen()) {
                return 'skipped';
            }

            if (!$ignoreSchedule && $locked->next_attempt_at && $locked->next_attempt_at->isFuture()) {
                return 'skipped';
            }

            $card = $locked->merchant->activeBillingCard;

            if (!$card || !$card->telr_tran_ref) {
                return $this->flagAwaitingCard($locked);
            }

            $attemptNumber = $locked->attempts + 1;
            $attempt = BillingChargeAttempt::create([
                'billing_statement_id' => $locked->id,
                'attempt_number'       => $attemptNumber,
                'cartid'               => sprintf('%s-A%d', $locked->number, $attemptNumber),
                'amount'               => $locked->fee_amount,
                'outcome'              => BillingChargeAttempt::OUTCOME_PENDING,
            ]);

            $locked->update([
                'status'          => BillingStatement::STATUS_CHARGING,
                'attempts'        => $attemptNumber,
                'next_attempt_at' => null,
            ]);

            return [$locked, $attempt, $card->telr_tran_ref];
        });

        if (is_string($claim)) {
            return $claim;
        }

        [$locked, $attempt, $tranRef] = $claim;

        // 2. Call Telr (no database lock held while we wait on the network).
        $result = $this->telr->chargeContinuous(
            $tranRef,
            (float) $locked->fee_amount,
            $attempt->cartid,
            'Edfundo Pay fees ' . $locked->periodLabel()
        );

        // 3. Record what happened.
        return $this->recordResult($locked, $attempt, $result);
    }

    private function flagAwaitingCard(BillingStatement $statement): string
    {
        $nextCheck = now($this->timezone())->addDay()->setTime(9, 0)->setTimezone(config('app.timezone'));
        $firstTime = $statement->awaiting_card_notified_at === null;

        $statement->update([
            'status'                    => BillingStatement::STATUS_AWAITING_CARD,
            'next_attempt_at'           => $nextCheck,
            'awaiting_card_notified_at' => $statement->awaiting_card_notified_at ?? now(),
        ]);

        // Only the first transition makes noise; the daily re-check is silent
        // until the merchant adds a card and it gets charged.
        if ($firstTime) {
            Log::error('Billing: no card on file to charge', [
                'statement' => $statement->number,
                'merchant'  => $statement->merchant->name,
            ]);
            $this->sendResultEmail($statement, 'awaiting_card');
        }

        return 'awaiting_card';
    }

    /** @param array{outcome: string, tran_ref: ?string, code: ?string, message: ?string} $result */
    private function recordResult(BillingStatement $statement, BillingChargeAttempt $attempt, array $result): string
    {
        $outcome = $result['outcome'];

        $attempt->update([
            'outcome'       => $outcome,
            'telr_tran_ref' => $result['tran_ref'],
            'telr_code'     => $result['code'],
            'message'       => $result['message'],
        ]);

        if ($outcome === TelrService::OUTCOME_AUTHORISED) {
            $statement->update([
                'status'          => BillingStatement::STATUS_PAID,
                'paid_at'         => now(),
                'paid_method'     => 'card',
                'telr_tran_ref'   => $result['tran_ref'],
                'next_attempt_at' => null,
                'failure_message' => null,
            ]);
            $this->sendResultEmail($statement, 'paid');

            return 'paid';
        }

        if ($outcome === TelrService::OUTCOME_UNKNOWN) {
            $statement->update([
                'status'          => BillingStatement::STATUS_NEEDS_REVIEW,
                'next_attempt_at' => null,
                'failure_message' => $result['message'] ?: 'The payment provider did not give a clear answer.',
            ]);
            Log::error('Billing: charge outcome unknown — check Telr before retrying', ['statement' => $statement->number]);

            return 'needs_review';
        }

        // Declined, or rejected as an error: definitely not charged.
        $firstFailedAt = $statement->first_failed_at ?? now();
        $message = $result['message'] ?: 'The card was declined.';

        if ($statement->attempts < $this->maxAttempts()) {
            $days = (int) config('billing.retry_after_days')[$statement->attempts - 1];
            $retryAt = $firstFailedAt->copy()->setTimezone($this->timezone())->addDays($days)->setTime(9, 0)->setTimezone(config('app.timezone'));

            $statement->update([
                'status'          => BillingStatement::STATUS_ISSUED,
                'first_failed_at' => $firstFailedAt,
                'next_attempt_at' => $retryAt,
                'failure_message' => $message,
            ]);
            $this->sendResultEmail($statement, 'retry', $retryAt);

            return 'retry_scheduled';
        }

        $statement->update([
            'status'          => BillingStatement::STATUS_FAILED,
            'first_failed_at' => $firstFailedAt,
            'next_attempt_at' => null,
            'failure_message' => $message,
        ]);
        Log::error('Billing: all charge attempts failed', [
            'statement' => $statement->number,
            'merchant'  => $statement->merchant->name,
            'amount'    => $statement->fee_amount,
            'reason'    => $message,
        ]);
        $this->sendResultEmail($statement, 'failed');

        return 'failed';
    }

    private function sendResultEmail(BillingStatement $statement, string $kind, ?Carbon $retryAt = null): void
    {
        try {
            Mail::to($this->recipientFor($statement->merchant))->send(new BillingChargeResultMail($statement, $kind, $retryAt));
        } catch (\Throwable $e) {
            Log::error('Billing: result email failed', ['statement' => $statement->number, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }

    // ------------------------------------------------------------------
    // Admin actions
    // ------------------------------------------------------------------

    public function markPaidManually(BillingStatement $statement, string $note): void
    {
        $statement->update([
            'status'          => BillingStatement::STATUS_PAID,
            'paid_at'         => now(),
            'paid_method'     => 'manual',
            'next_attempt_at' => null,
            'failure_message' => $note,
        ]);
        Log::warning('Billing: statement marked paid manually', ['statement' => $statement->number, 'note' => $note]);
    }

    public function void(BillingStatement $statement): bool
    {
        if (in_array($statement->status, [BillingStatement::STATUS_PAID, BillingStatement::STATUS_CHARGING], true)) {
            return false;
        }

        $statement->update(['status' => BillingStatement::STATUS_VOID, 'next_attempt_at' => null]);
        Log::warning('Billing: statement voided', ['statement' => $statement->number]);

        return true;
    }

    /**
     * Start a fresh charge cycle and try the card straight away. For a
     * statement under review the caller must have confirmed in Telr that
     * nothing was charged, or the merchant could be charged twice.
     */
    public function retryNow(BillingStatement $statement): string
    {
        if (!in_array($statement->status, [
            BillingStatement::STATUS_FAILED,
            BillingStatement::STATUS_AWAITING_CARD,
            BillingStatement::STATUS_NEEDS_REVIEW,
            BillingStatement::STATUS_ISSUED,
        ], true)) {
            return 'skipped';
        }

        $statement->update([
            'status'          => BillingStatement::STATUS_ISSUED,
            'attempts'        => 0,
            'first_failed_at' => null,
            'next_attempt_at' => now(),
            'failure_message' => null,
        ]);

        return $this->charge($statement->fresh(), true);
    }

    // ------------------------------------------------------------------
    // Card registration
    // ------------------------------------------------------------------

    /** @return array{success: bool, url?: string, error?: string} */
    public function startCardSetup(Merchant $merchant, string $ip): array
    {
        $cartId = sprintf('EPCARD-%d-%s', $merchant->id, Str::upper(Str::random(10)));

        $card = MerchantBillingCard::create([
            'merchant_id'  => $merchant->id,
            'status'       => MerchantBillingCard::STATUS_PENDING,
            'setup_cartid' => $cartId,
            'consent_at'   => now(),
            'consent_ip'   => $ip,
        ]);

        $return = route('merchant.billing.card.return', ['cart' => $cartId]);

        $result = $this->telr->createCardSetupOrder($cartId, (float) config('billing.verification_amount', 1.00), [
            'authorised' => $return,
            'declined'   => $return,
            'cancelled'  => $return,
        ]);

        if (!$result['success']) {
            $card->update(['status' => MerchantBillingCard::STATUS_FAILED]);

            return ['success' => false, 'error' => $result['error'] ?? 'Could not start card registration.'];
        }

        $card->update(['telr_order_ref' => $result['order_ref']]);

        return ['success' => true, 'url' => $result['url']];
    }

    /**
     * Check with Telr how a card registration ended and activate the card.
     *
     * @return string activated | already_active | pending | declined | error
     */
    public function completeCardSetup(MerchantBillingCard $card): string
    {
        if ($card->status === MerchantBillingCard::STATUS_ACTIVE) {
            return 'already_active';
        }

        if ($card->status !== MerchantBillingCard::STATUS_PENDING || !$card->telr_order_ref) {
            return 'declined';
        }

        $check = $this->telr->checkOrder($card->telr_order_ref);

        if (!$check['success']) {
            return 'error';
        }

        if (!$check['paid']) {
            // Still on Telr's payment page (e.g. the "check again" button).
            if (stripos($check['status_text'] ?? '', 'pending') !== false) {
                return 'pending';
            }

            $card->update(['status' => MerchantBillingCard::STATUS_FAILED]);

            return 'declined';
        }

        $activated = DB::transaction(function () use ($card, $check) {
            $locked = MerchantBillingCard::whereKey($card->id)->lockForUpdate()->first();

            if ($locked->status !== MerchantBillingCard::STATUS_PENDING) {
                return false;
            }

            MerchantBillingCard::where('merchant_id', $locked->merchant_id)
                ->where('status', MerchantBillingCard::STATUS_ACTIVE)
                ->update(['status' => MerchantBillingCard::STATUS_REPLACED]);

            $locked->update([
                'status'        => MerchantBillingCard::STATUS_ACTIVE,
                'telr_tran_ref' => $check['tran_ref'],
                'card_type'     => $check['card']['type'],
                'last4'         => $check['card']['last4'],
                'expiry_month'  => $check['card']['expiry_month'],
                'expiry_year'   => $check['card']['expiry_year'],
            ]);

            return true;
        });

        if (!$activated) {
            return 'already_active';
        }

        // The AED 1 registration sale is deliberately NOT refunded here. On a
        // live account a same-day "refund" of an unsettled card payment is
        // processed by Telr as a VOID, which cancels the original sale — and
        // the monthly continuous-authority charges refer back to exactly that
        // sale, so they were rejected with "Original transaction not
        // authorised". (Test mode doesn't imitate this.) Returning the AED 1
        // has to wait until the sale has settled, or be done as a credit.

        return 'activated';
    }
}
