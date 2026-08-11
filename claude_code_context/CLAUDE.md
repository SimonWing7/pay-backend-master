# Edfundo Pay — Claude Code Context

## What this project is
Edfundo Pay is a Laravel 12 hosted payment platform that allows schools and merchants to collect payments from parents via Lean Open Finance (Open Banking). Merchants embed a payment link or integrate via API; parents pay via their bank app. Built and maintained by Simon Wing (simon@edfundo.com).

## Servers

### Edfundo Pay — Staging
- **URL:** https://staging-pay.edfundo.com
- **Server IP:** 54.93.82.1 (AWS Lightsail, Singapore ap-southeast-1)
- **SSH:** Via AWS Lightsail browser console (preferred) or `ssh ubuntu@staging-pay.edfundo.com`
- **Internal hostname:** ip-172-26-1-195
- **Project root:** /var/www/edfundo-pay/
- **Web user:** www-data
- **Deploy user:** ubuntu / admin

### J20 Sports — UAT Magento site (external merchant)
- **URL:** https://uat-edfundo.j20sports.com/
- **Admin:** https://uat-edfundo.j20sports.com/admin (user: edfundo / pass: R-N1_@U3*f)
- **Server IP:** 18.133.238.220
- **SSH:** `ssh -i ~/.ssh/J20-UAT-Edfundo.pem ubuntu@18.133.238.220`
- **SSH key:** Stored in 1Password as "J20-UAT-Edfundo"
- **SSH whitelist:** Only 54.93.82.1 (Edfundo Pay Lightsail IP) is whitelisted — always SSH to J20 VIA the Lightsail server, not directly from a laptop
- **Project root:** /var/www/j20248/
- **Deploy user:** `sudo su deploy`
- **Magento logs:** /var/www/j20248/var/log/exception.log and /var/www/j20248/var/log/system.log

## Tech stack
- **Framework:** Laravel 12
- **PHP:** 8.x
- **Database:** MySQL (via Eloquent ORM)
- **Payment gateway:** Lean Open Finance (webhooks + REST API)
- **Email:** AWS SES via SMTP (Singapore region, ap-southeast-1)
- **Queue:** Laravel queue (check .env for driver)
- **Frontend:** Blade templates + inline CSS for emails

## Key files and their purpose

### Models
- `app/Models/AppUserPayment.php` — core payment record. Fields: `customer_name`, `customer_email`, `customer_mobile`, `custom_field_values` (JSON string OR array — always check with `is_array()` before using), `lean_payment_intent_id`, `status` (enum: `PaymentStatus`)
- `app/Models/Invoice.php` — fields: `total_fee`, `reference`. Relationships: `merchant()`, `appUserPayment()`
- `app/Models/Merchant.php` — fields: `id`, `name`. Used in referral tracking

### Controllers
- `app/Http/Controllers/LeanWebhookController.php` — handles Lean payment gateway webhooks. `ACCEPTED_SETTLEMENT_COMPLETED` is the only status that triggers payment confirmation and sends the receipt email. Always eager-load with `->with('invoice.merchant')` (not just `->with('invoice')`)
- `app/Http/Controllers/PublicInvoiceController.php` — serves the hosted payment page
- `app/Http/Controllers/MerchantApiController.php` — API for merchants (has been patched for Dubai Marlins integration)

### Email
- `app/Mail/PaymentReceipt.php` — Mailable class. Accepts `AppUserPayment $payment`. Subject: "Payment Receipt - {invoice->reference}"
- `resources/views/emails/payment-receipt.blade.php` — HTML email template (inline CSS, table layout, email-safe)

### Magento module (external — lives on J20 server)
- Module namespace: `Edfundo\Pay`
- Key controller: `Edfundo\Pay\Controller\Payment\Redirect` — calls `POST /api/v1/payment-links` on staging-pay.edfundo.com and redirects to hosted payment page
- Return controller: `Edfundo\Pay\Controller\Payment\ReturnAction` — verifies payment via `GET /api/v1/payment-links/{uuid}`

## Brand colours
- **Edfundo blue:** `#3d01bd` — used on "Pay" text, amounts, CTA buttons, footer links
- **Green tick:** `#3DBA8C` — kept green intentionally (success/confirmation only)
- **Amount box background:** `#ece8fc` (light violet)

## Common commands on the Edfundo Pay server

```bash
# Clear compiled views after any blade template change
sudo php /var/www/edfundo-pay/artisan view:clear

# Clear config cache
sudo php /var/www/edfundo-pay/artisan config:clear

# Syntax check a PHP file
php -l /var/www/edfundo-pay/path/to/file.php

# Fix file ownership after edits
sudo chown www-data:www-data /path/to/file

# Run Laravel code without tinker (psysh has permission issues — DO NOT use artisan tinker)
cat << 'EOF' | sudo php
<?php
define('LARAVEL_START', microtime(true));
require '/var/www/edfundo-pay/vendor/autoload.php';
$app = require_once '/var/www/edfundo-pay/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Laravel facades available here — e.g. Mail::, DB::, App\Models\...
EOF
```

## Deployment pattern
Changes are deployed by writing Python patch scripts, base64-encoding them, and piping to the server:
```bash
echo "<base64_string>" | base64 -d | sudo python3
```
Scripts should: read the file, make targeted string replacements, write back, `chown www-data:www-data`, run `view:clear` or `config:clear` as needed, and print a verification summary.

## Known gotchas
- **Never use `php artisan tinker`** — psysh throws "Writing to /var/www/.config/psysh is not allowed". Use the PHP bootstrap one-liner above instead.
- **`custom_field_values`** on AppUserPayment may be a JSON string or a PHP array — always handle both: `$fields = is_array($raw) ? $raw : (json_decode($raw, true) ?? [])`
- **Dubai timezone:** Use `now('Asia/Dubai')` for GST (UTC+4, no daylight saving)
- **Email dispatch:** Always wrap `Mail::to()->send()` in `try/catch (\Throwable $e)` so email failures don't cause webhook errors (which would trigger Lean retries and potential double-processing)
- **Merchant eager-loading:** Must use `->with('invoice.merchant')` not just `->with('invoice')` when the merchant is needed (e.g. for reward CTA referral URL)
- **J20 SSH access:** Only the Lightsail server IP (54.93.82.1) is whitelisted. SSH to J20 must go via Lightsail browser console → Lightsail terminal → `ssh -i ~/.ssh/J20-UAT-Edfundo.pem ubuntu@18.133.238.220`

## Current work in progress

### J20 Sports Magento checkout bug (ACTIVE — top priority)
Customers selecting "Pay by Bank (Edfundo Pay)" at checkout are not being redirected to the Edfundo Pay hosted payment page. The order is created in Magento, but the checkout hangs or returns a 400 on Magento's own `payment-information` REST endpoint. The Edfundo Pay `Redirect` controller never runs. This is confirmed to be a Magento-side issue — the Edfundo Pay backend and Lean integration are working correctly for the J20 merchant account.

**Next step:** Pull Magento exception.log and system.log from the J20 server for these timestamps and analyse the PHP stack traces:
- Order #000000006 — Jul 31, 2026 ~1:35 PM
- Order #000000007 — Aug 3, 2026 ~11:46 PM (HTTP 400 after 11s)
- Order #000000008 — Aug 3, 2026 ~11:49 PM (Check/Money Order, succeeded — comparison baseline)

### AED 50 Reward CTA in payment receipt email (DEPLOYED to staging)
The payment receipt email now includes a full-width Edfundo blue banner above the disclaimer note, with a "Claim Your AED 50 Reward" heading and a CTA button linking to:
`https://edfundo.com/edfundo-pay-rewards/?ref={merchant_id}&utm_source=edfundo_pay&utm_medium=payment_receipt`
Needs to be deployed to production when production server is ready.

## Other pending items
- Share Edfundo_Referral_CR_v2.docx with app dev team
- Verify admin referral routes are working
- Test Dubai Marlins integration after MerchantApiController fix
- Set EDFUNDO_REFERRAL_SIGNING_SECRET in production .env (must differ from staging value)
