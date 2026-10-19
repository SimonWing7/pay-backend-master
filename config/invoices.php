<?php

// How long a "personal" (single-use) Draft invoice can sit unpaid before
// it's automatically marked Failed by the invoices:expire-stale command.
// "Open" link_type invoices are deliberately excluded from this — they're
// designed to be reused indefinitely (shared group links), so auto-expiring
// them would break their entire purpose.
return [
    'expiry_hours' => (int) env('INVOICE_EXPIRY_HOURS', 24),

    // Most payers a single CSV upload may contain — a guard against a runaway
    // or wrong file rather than a real-world limit.
    'max_import_rows' => (int) env('INVOICE_MAX_IMPORT_ROWS', 5000),

    // Payment-link emails sent per run of payers:send-links (every minute).
    // Sent one at a time through SES, so this keeps each run well inside a
    // minute and under SES's send rate.
    'send_batch_size' => (int) env('INVOICE_SEND_BATCH_SIZE', 100),

    // A Lean payment stuck at PENDING_WITH_BANK this long is flagged for
    // manual reconciliation — Lean treats that status as final and never
    // follows up, so it won't resolve on its own.
    'pending_review_hours' => (int) env('PAYMENT_PENDING_REVIEW_HOURS', 6),

    // How long a merchant who can reconcile their own payments has to act
    // before Edfundo is alerted about it too.
    'pending_escalate_hours' => (int) env('PAYMENT_PENDING_ESCALATE_HOURS', 48),
];
