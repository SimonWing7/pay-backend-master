# Skill: Debugging the J20 Sports Magento integration

## Context
J20 Sports has a Magento 2 UAT site at uat-edfundo.j20sports.com with the Edfundo Pay module installed. There is an active bug where checkout hangs/returns 400 when Edfundo Pay is selected. See CLAUDE.md for full details.

## SSH access — IMPORTANT
Only the Edfundo Pay Lightsail server IP (54.93.82.1) is whitelisted on J20's SSH firewall.

**You MUST SSH to J20 via the Lightsail server:**
1. Open AWS Lightsail browser console for staging-pay.edfundo.com
2. At the `admin@ip-172-26-1-195` prompt, ensure the J20 key exists:
   ```bash
   ls ~/.ssh/J20-UAT-Edfundo.pem
   # If missing, paste from 1Password:
   nano ~/.ssh/J20-UAT-Edfundo.pem
   chmod 400 ~/.ssh/J20-UAT-Edfundo.pem
   ```
3. SSH to J20:
   ```bash
   ssh -i ~/.ssh/J20-UAT-Edfundo.pem ubuntu@18.133.238.220
   ```
4. Switch to deploy user:
   ```bash
   sudo su deploy
   ```

Do NOT attempt to SSH to J20 from a Mac or any other IP — it will time out.

## Log locations on J20 server
```
/var/www/j20248/var/log/exception.log   — PHP exceptions with stack traces
/var/www/j20248/var/log/system.log      — Magento system messages
/var/www/j20248/var/log/debug.log       — debug output (may not exist)
```

## Pulling logs for the failing orders
```bash
# Check server timezone first
date
tail -5 /var/www/j20248/var/log/exception.log

# Order #000000006 — Jul 31, 2026 ~1:35 PM
grep -A 50 "2026-07-31 1[0-9]:" /var/www/j20248/var/log/exception.log | head -300

# Order #000000007 and nearby — Aug 3, 2026 ~11:46 PM
grep -A 50 "2026-08-03 2[0-9]:" /var/www/j20248/var/log/exception.log | head -300

# System log same windows
grep -A 10 "2026-07-31 1[0-9]:" /var/www/j20248/var/log/system.log | head -150
grep -A 10 "2026-08-03 2[0-9]:" /var/www/j20248/var/log/system.log | head -150
```

## Magento module structure (on J20 server)
Module location: `/var/www/j20248/app/code/Edfundo/Pay/` (or may be in `/var/www/j20248/vendor/`)

Key files to check if logs point to module issues:
- `Controller/Payment/Redirect.php` — calls staging-pay.edfundo.com, redirects to hosted payment page
- `Controller/Payment/ReturnAction.php` — handles post-payment return
- `Model/` — payment method model
- `etc/config.xml` and `etc/payment.xml` — module configuration

## Magento CLI commands (on J20 server as deploy user)
```bash
cd /var/www/j20248

# Check module status
php bin/magento module:status | grep -i edfundo

# Clear caches after any change
php bin/magento cache:clean
php bin/magento cache:flush

# Check for any pending setup upgrades
php bin/magento setup:upgrade --dry-run

# View recent orders
php bin/magento sales:order:list  # may not exist; check admin instead
```

## Magento admin access
- URL: https://uat-edfundo.j20sports.com/admin
- Username: edfundo
- Password: R-N1_@U3*f
- Check Sales → Orders for order statuses and payment method on each order

## What we know about the bug
- Magento's `POST /rest/default/V1/guest-carts/{cartId}/payment-information` returns 400 after ~11 seconds
- Order still gets created in Magento despite the 400 (so it's not a validation failure before order commit)
- The 400 is JSON (not an HTML block page) — `Content-Type: application/json` confirmed in response headers
- Cloudflare is in front of the origin
- Check/Money Order works fine — the failure is specific to Edfundo Pay
- The `Edfundo\Pay\Controller\Payment\Redirect` controller never runs (never reaches the Edfundo Pay API)
- Most likely cause: an exception being thrown in the Edfundo Pay payment method model or an observer/plugin triggered after order placement

## Edfundo Pay API endpoints (for reference when module calls them)
- `POST https://staging-pay.edfundo.com/api/v1/payment-links` — creates a draft invoice, returns `payment_url`
- `GET https://staging-pay.edfundo.com/api/v1/payment-links/{uuid}` — verifies payment status
