<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Consumer;
use App\Models\MerchantReferral;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReferralImportService extends Service
{
    /**
     * Import a daily export from the main Edfundo app's admin panel.
     *
     * There's no referral code captured at signup (would need main-app dev
     * work we're deliberately avoiding), so attribution is by matching
     * email OR mobile number against Edfundo Pay's own Consumer/Invoice
     * records instead — confirmed with the business that the same parent
     * doesn't reliably use the same email for both the payment and the
     * Edfundo signup (sometimes a different parent entirely), so either
     * identifier matching is treated as a hit.
     *
     * Only rows with Subscription Status = Active are processed at all —
     * confirmed with business: "Free" is a manually-discounted rate and
     * "trial" hasn't paid, neither meets the full-fee-paid requirement for
     * the AED 50. Attribution is checked against Subscription Start Date,
     * not SignUp Date — the trigger is the paid conversion, not the
     * signup itself, which may happen well before (or same-day as) the
     * payment that referred them.
     *
     * @return array{rows: int, matched: int, earned: int, skipped_not_active: int, skipped_no_match: int, skipped_invalid: int}
     */
    public function import(string $csvContents): array
    {
        $rows = $this->parseRows($csvContents);

        $stats = [
            'rows' => 0, 'matched' => 0, 'earned' => 0,
            'skipped_not_active' => 0, 'skipped_no_match' => 0, 'skipped_invalid' => 0,
        ];

        foreach ($rows as $row) {
            $stats['rows']++;

            $email = trim((string) ($row['Email'] ?? ''));
            $mobile = trim((string) ($row['Mobile Number'] ?? ''));
            $userId = trim((string) ($row['User Id'] ?? ''));
            $isActive = strtolower(trim((string) ($row['Subscription Status'] ?? ''))) === 'active';
            $subscriptionStart = $this->parseDate($row['Subscription Start Date (yyyy-MM-DD)'] ?? null);

            if (!$userId || (!$email && !$mobile)) {
                $stats['skipped_invalid']++;
                continue;
            }

            if (!$isActive || !$subscriptionStart) {
                // Not a commission-earning event (yet) — nothing to
                // attribute without a subscription date to check the
                // payment against. A later import will pick this row up
                // once/if it becomes Active.
                $stats['skipped_not_active']++;
                continue;
            }

            $consumer = $this->findReferringConsumer($email, $mobile, $subscriptionStart);

            if (!$consumer) {
                $stats['skipped_no_match']++;
                continue;
            }

            $stats['matched']++;

            $signUpDate = $this->parseDate($row['SignUp Date (yyyy-MM-DD)'] ?? null);

            $referral = MerchantReferral::firstOrNew([
                'merchant_uuid' => (string) $consumer->merchant_id,
                'edfundo_user_id' => $userId,
            ]);

            $referral->edfundo_user_email = $email ?: $referral->edfundo_user_email;
            $referral->registered_at = $referral->registered_at ?? $signUpDate ?? $subscriptionStart;
            $referral->registered_payload = $row;

            // Never regress an already-settled commission back to earned
            // on a later re-import of the same active row.
            if ($referral->commission_status !== 'settled') {
                $referral->subscription_plan = 'active';
                $referral->subscribed_at = $referral->subscribed_at ?? $subscriptionStart;
                $referral->subscribed_payload = $row;
                $referral->commission_status = 'earned';
                $stats['earned']++;
            }

            $referral->save();
        }

        return $stats;
    }

    private function findReferringConsumer(string $email, string $mobile, Carbon $cutoffDate): ?Consumer
    {
        $normalizedMobile = $this->normalizeMobile($mobile);

        if (!$email && !$normalizedMobile) {
            return null;
        }

        /** @var Collection<int, Consumer> $candidates */
        $candidates = Consumer::query()
            ->where(function ($q) use ($email, $normalizedMobile) {
                if ($email) {
                    $q->orWhere('email', $email);
                }
                if ($normalizedMobile) {
                    // Coarse DB filter (suffix match); refined precisely
                    // below since a leading-wildcard LIKE can false-match
                    // on a shorter number that happens to be a substring.
                    $q->orWhere('mobile_number', 'like', '%' . $normalizedMobile);
                }
            })
            ->with(['invoices' => function ($q) use ($cutoffDate) {
                $q->where('status', InvoiceStatus::Paid)
                    ->whereDate('created_at', '<=', $cutoffDate)
                    ->orderByDesc('created_at');
            }])
            ->get()
            ->filter(function (Consumer $c) use ($email, $normalizedMobile) {
                if ($email && $c->email === $email) {
                    return true;
                }
                return $normalizedMobile && $this->normalizeMobile($c->mobile_number) === $normalizedMobile;
            })
            ->filter(fn (Consumer $c) => $c->invoices->isNotEmpty());

        return $candidates
            ->sortByDesc(fn (Consumer $c) => $c->invoices->first()->created_at)
            ->first();
    }

    /**
     * Strips everything but digits and compares on the last 9 — UAE mobile
     * subscriber numbers are always 9 digits regardless of whether the
     * source formats with +971, 971, or a leading 0.
     */
    private function normalizeMobile(?string $mobile): ?string
    {
        if (!$mobile) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $mobile);

        return $digits && strlen($digits) >= 9 ? substr($digits, -9) : null;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parseRows(string $csvContents): array
    {
        // Strip a UTF-8 BOM if present — common when a file is exported
        // from Excel, and would otherwise corrupt the first header key.
        $csvContents = preg_replace('/^\xEF\xBB\xBF/', '', $csvContents);

        $lines = preg_split('/\r\n|\r|\n/', trim($csvContents));
        if (empty($lines)) {
            return [];
        }

        // Seen this export both tab- and comma-delimited depending on how
        // it's downloaded/re-saved — detect rather than assume.
        $delimiter = substr_count($lines[0], "\t") > substr_count($lines[0], ',') ? "\t" : ',';

        $header = array_map(
            fn ($h) => trim($h, " \t\n\r\0\x0B\""),
            str_getcsv(array_shift($lines), $delimiter)
        );

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = array_map(
                fn ($f) => trim((string) $f, " \t\n\r\0\x0B\""),
                str_getcsv($line, $delimiter)
            );

            // Tolerate a row with a different field count than the header
            // (a trailing delimiter, a missing final column) rather than
            // letting array_combine fail the whole row.
            $fields = array_slice(array_pad($fields, count($header), null), 0, count($header));

            $rows[] = array_combine($header, $fields);
        }

        return $rows;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
