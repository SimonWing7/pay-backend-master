<?php

// How long a "personal" (single-use) Draft invoice can sit unpaid before
// it's automatically marked Failed by the invoices:expire-stale command.
// "Open" link_type invoices are deliberately excluded from this — they're
// designed to be reused indefinitely (shared group links), so auto-expiring
// them would break their entire purpose.
return [
    'expiry_hours' => (int) env('INVOICE_EXPIRY_HOURS', 24),

    // A Lean payment stuck at PENDING_WITH_BANK this long is flagged for
    // manual reconciliation — Lean treats that status as final and never
    // follows up, so it won't resolve on its own.
    'pending_review_hours' => (int) env('PAYMENT_PENDING_REVIEW_HOURS', 6),
];
