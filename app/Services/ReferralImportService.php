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
     * Rows with Subscription Status = Active, Pending, or Trial are all
     * imported now, so Edfundo has visibility into who's started the
     * onboarding/subscription process even before they've paid — "Free"
     * (a manually-discounted rate) is still excluded, per the business
     * confirming that's not a real signup either. Only Active rows count
     * as a commission-earning event though — Pending/Trial rows show up
     * in the dashboard with their real status but no commission until a
     * later import shows them as Active. Attribution is checked against
     * Subscription Start Date where available; Pending rows often don't
     * have one yet (nothing's started billing), so SignUp Date is used
     * as the fallback cutoff for the payment-history match in that case.
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
            $rawStatus = trim((string) ($row['Subscription Status'] ?? ''));
            $statusLower = strtolower($rawStatus);
            $isActive = $statusLower === 'active';
            $isTrackable = in_array($statusLower, ['active', 'pending', 'trial'], true);
            $subscriptionStart = $this->parseDate($row['Subscription Start Date (yyyy-MM-DD)'] ?? null);
            $signUpDate = $this->parseDate($row['SignUp Date (yyyy-MM-DD)'] ?? null);

            if (!$userId || (!$email && !$mobile)) {
                $stats['skipped_invalid']++;
                continue;
            }

            // "Free" or anything else unrecognized — not a real signup
            // attempt, deliberately excluded.
            $cutoffDate = $subscriptionStart ?? $signUpDate;
            if (!$isTrackable || !$cutoffDate) {
                $stats['skipped_not_active']++;
                continue;
            }

            $consumer = $this->findReferringConsumer($email, $mobile, $cutoffDate);

            if (!$consumer) {
                $stats['skipped_no_match']++;
                continue;
            }

            $stats['matched']++;

            $referral = MerchantReferral::firstOrNew([
                'merchant_uuid' => (string) $consumer->merchant_id,
                'edfundo_user_id' => $userId,
            ]);

            $referral->edfundo_user_email = $email ?: $referral->edfundo_user_email;
            $referral->registered_at = $referral->registered_at ?? $signUpDate ?? $subscriptionStart;
            $referral->registered_payload = $row;

            // Never regress an already-settled commission, and never let a
            // later "no longer Active" row erase a commission already
            // earned from a prior import — only ever move forward.
            if (!in_array($referral->commission_status, ['settled', 'earned'], true)) {
                $referral->subscription_plan = $rawStatus;

                if ($isActive) {
                    $referral->subscribed_at = $referral->subscribed_at ?? $subscriptionStart;
                    $referral->subscribed_payload = $row;
                    $referral->commission_status = 'earned';
                    $stats['earned']++;
                } else {
                    // Pending/Trial — visible in both dashboards, but not
                    // yet a commission-earning event. The admin dashboard's
                    // stat card explicitly counts the literal string
                    // 'pending', so this can't be left null.
                    $referral->commission_status = 'pending';
                }
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

        // Excel silently mangles long numeric-looking phone numbers into
        // lossy scientific notation (e.g. "9.71503E+11") unless the
        // column is explicitly formatted as text — the original trailing
        // digits are gone by that point, not just reformatted, so treat
        // it as unusable rather than extract garbage digits from it.
        if (preg_match('/^\d(\.\d+)?E\+?\d+$/i', trim($mobile))) {
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

        $value = trim($value);

        // The real export has come through as DD/MM/YYYY (Excel's locale
        // display formatting) despite the column header claiming
        // yyyy-MM-DD — try that explicitly first rather than relying on
        // Carbon's generic slash-date guessing, which assumes US ordering
        // and would misread or fail on it entirely.
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $date = Carbon::createFromFormat('!' . $format, $value);
            if ($date !== false) {
                return $date;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception) {
            return null;
        }
    }
}
