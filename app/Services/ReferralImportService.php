<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Consumer;
use App\Models\MerchantReferral;
use Illuminate\Support\Carbon;

class ReferralImportService extends Service
{
    /**
     * Import a daily export from the main Edfundo app's admin panel.
     *
     * There's no referral code captured at signup (would need main-app dev
     * work we're deliberately avoiding), so attribution is by email match
     * against Edfundo Pay's own Consumer/Invoice records instead: if this
     * person has a PAID invoice through one of our merchants dated before
     * their signup date, that's a referral. Where a match exists under
     * more than one merchant, the most recent qualifying payment before
     * signup wins (last-touch).
     *
     * Only "Active" subscription status counts as commission-earning —
     * confirmed with the business: "Free" is a manually-discounted rate
     * and "trial" hasn't paid at all, neither meets the requirement of
     * having paid the full subscription fee.
     *
     * @return array{rows: int, matched: int, earned: int, skipped_no_match: int, skipped_invalid: int}
     */
    public function import(string $csvContents): array
    {
        $rows = $this->parseRows($csvContents);

        $stats = ['rows' => 0, 'matched' => 0, 'earned' => 0, 'skipped_no_match' => 0, 'skipped_invalid' => 0];

        foreach ($rows as $row) {
            $stats['rows']++;

            $email = trim($row['Email'] ?? '');
            $userId = trim($row['User Id'] ?? '');
            $signUpDate = $this->parseDate($row['SignUp Date (yyyy-MM-DD)'] ?? null);

            if (!$email || !$userId || !$signUpDate) {
                $stats['skipped_invalid']++;
                continue;
            }

            $consumer = $this->findReferringConsumer($email, $signUpDate);

            if (!$consumer) {
                $stats['skipped_no_match']++;
                continue;
            }

            $stats['matched']++;

            $isActive = strtolower(trim($row['Subscription Status'] ?? '')) === 'active';

            $referral = MerchantReferral::firstOrNew([
                'merchant_uuid' => (string) $consumer->merchant_id,
                'edfundo_user_id' => $userId,
            ]);

            $referral->edfundo_user_email = $email;
            $referral->registered_at = $referral->registered_at ?? $signUpDate;
            $referral->registered_payload = $row;

            // Never regress an already-settled commission back to earned
            // on a later re-import of the same active row.
            if ($isActive && $referral->commission_status !== 'settled') {
                $referral->subscription_plan = 'active';
                $referral->subscribed_at = $referral->subscribed_at
                    ?? $this->parseDate($row['Subscription Start Date (yyyy-MM-DD)'] ?? null)
                    ?? $signUpDate;
                $referral->subscribed_payload = $row;
                $referral->commission_status = 'earned';
                $stats['earned']++;
            }

            $referral->save();
        }

        return $stats;
    }

    private function findReferringConsumer(string $email, Carbon $signUpDate): ?Consumer
    {
        return Consumer::where('email', $email)
            ->whereHas('invoices', function ($q) use ($signUpDate) {
                $q->where('status', InvoiceStatus::Paid)
                    ->whereDate('created_at', '<=', $signUpDate);
            })
            ->with(['invoices' => function ($q) use ($signUpDate) {
                $q->where('status', InvoiceStatus::Paid)
                    ->whereDate('created_at', '<=', $signUpDate)
                    ->orderByDesc('created_at');
            }])
            ->get()
            ->sortByDesc(fn (Consumer $c) => optional($c->invoices->first())->created_at)
            ->first();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseRows(string $csvContents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContents));
        if (empty($lines)) {
            return [];
        }

        // The export has been seen both tab- and comma-delimited depending
        // on how it's downloaded — detect rather than assume.
        $delimiter = substr_count($lines[0], "\t") > substr_count($lines[0], ',') ? "\t" : ',';

        $header = str_getcsv(array_shift($lines), $delimiter);

        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $fields = str_getcsv($line, $delimiter);
            $rows[] = array_combine($header, array_pad($fields, count($header), null));
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
