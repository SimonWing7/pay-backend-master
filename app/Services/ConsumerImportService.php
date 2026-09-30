<?php

namespace App\Services;

use App\Models\Consumer;

class ConsumerImportService extends Service
{
    /**
     * Bulk-create/update Consumer records for a merchant from a CSV of
     * parent/student contacts, so they're ready to check off on the Bulk
     * Invoices page without adding them one at a time first.
     *
     * Expected columns (case-insensitive, order-tolerant): Parent Name,
     * Student/Player Name, Parent Email, Parent Mobile. Matches an existing
     * Consumer for this merchant by email or mobile so re-uploading the
     * same list (e.g. with a new student added) updates rather than
     * duplicates.
     *
     * @return array{rows: int, created: int, updated: int, skipped: int}
     */
    public function import(string $csvContents, int $merchantId): array
    {
        $rows = $this->parseRows($csvContents);

        $stats = ['rows' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            $stats['rows']++;

            $name = trim((string) $this->firstValue($row, ['parent name', 'name']));
            $studentName = trim((string) $this->firstValue($row, ['student/player name', 'student name', 'player name', 'student']));
            $email = trim((string) $this->firstValue($row, ['parent email', 'email']));
            $mobile = trim((string) $this->firstValue($row, ['parent mobile', 'parent mobile number', 'mobile', 'mobile number', 'phone']));

            $normalizedMobile = $this->normalizeMobile($mobile);

            if (!$name || (!$email && !$normalizedMobile)) {
                $stats['skipped']++;
                continue;
            }

            $consumer = Consumer::where('merchant_id', $merchantId)
                ->where(function ($q) use ($email, $mobile) {
                    if ($email) {
                        $q->orWhere('email', $email);
                    }
                    if ($mobile) {
                        $q->orWhere('mobile_number', 'like', '%' . $this->normalizeMobile($mobile));
                    }
                })
                ->first();

            if ($consumer) {
                $consumer->update([
                    'name' => $name ?: $consumer->name,
                    'student_name' => $studentName ?: $consumer->student_name,
                    'email' => $email ?: $consumer->email,
                    'mobile_number' => $mobile ?: $consumer->mobile_number,
                ]);
                $stats['updated']++;
            } else {
                Consumer::create([
                    'merchant_id' => $merchantId,
                    'name' => $name,
                    'student_name' => $studentName ?: null,
                    'email' => $email ?: null,
                    'mobile_number' => $mobile ?: null,
                ]);
                $stats['created']++;
            }
        }

        return $stats;
    }

    private function firstValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return null;
    }

    /**
     * Strips everything but digits and compares on the last 9 — UAE mobile
     * subscriber numbers are always 9 digits regardless of whether the
     * source formats with +971, 971, or a leading 0. Same guard as
     * ReferralImportService against Excel's scientific-notation mangling of
     * long numeric-looking cells.
     */
    private function normalizeMobile(?string $mobile): ?string
    {
        if (!$mobile) {
            return null;
        }

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
        // Strip a UTF-8 BOM if present — common when exported from Excel.
        $csvContents = preg_replace('/^\xEF\xBB\xBF/', '', $csvContents);

        $lines = preg_split('/\r\n|\r|\n/', trim($csvContents));
        if (empty($lines)) {
            return [];
        }

        $delimiter = substr_count($lines[0], "\t") > substr_count($lines[0], ',') ? "\t" : ',';

        $header = array_map(
            fn ($h) => strtolower(trim($h, " \t\n\r\0\x0B\"")),
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

            // Tolerate a row with a different field count than the header.
            $fields = array_slice(array_pad($fields, count($header), null), 0, count($header));

            $rows[] = array_combine($header, $fields);
        }

        return $rows;
    }
}
