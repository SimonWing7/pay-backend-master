<?php

// How long a "personal" (single-use) Draft invoice can sit unpaid before
// it's automatically marked Failed by the invoices:expire-stale command.
// "Open" link_type invoices are deliberately excluded from this — they're
// designed to be reused indefinitely (shared group links), so auto-expiring
// them would break their entire purpose.
return [
    'expiry_hours' => (int) env('INVOICE_EXPIRY_HOURS', 24),
];
