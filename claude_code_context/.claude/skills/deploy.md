# Skill: Deploying changes to Edfundo Pay staging server

## When to use this skill
Use whenever making changes to files on the Edfundo Pay staging server at staging-pay.edfundo.com.

## Server access
- SSH via AWS Lightsail browser console (preferred) or `ssh ubuntu@staging-pay.edfundo.com`
- Project root: `/var/www/edfundo-pay/`
- Always fix ownership after writing files: `sudo chown www-data:www-data <file>`

## Deployment method — Python patch scripts
Write a Python script that makes targeted changes, then base64-encode and pipe it to the server.

### Script template
```python
#!/usr/bin/env python3
"""Brief description of what this patch does."""
import subprocess

path = '/var/www/edfundo-pay/path/to/file'

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

original = content

# Make changes
content = content.replace('OLD_STRING', 'NEW_STRING')

if content == original:
    print('WARNING: no changes detected — check the file matches expected content.')
else:
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    subprocess.run(['chown', 'www-data:www-data', path], check=True)

    # Verify
    checks = [
        ('Description of change', 'expected_string_in_content' in content),
    ]
    print('=== Patch applied ===\n')
    for label, ok in checks:
        print('  ' + ('OK  ' if ok else 'FAIL') + '  ' + label)

    # Clear caches
    r = subprocess.run(
        ['php', '/var/www/edfundo-pay/artisan', 'view:clear'],
        capture_output=True, text=True, cwd='/var/www/edfundo-pay'
    )
    print('\n  view:clear →', r.stdout.strip() or r.stderr.strip())
    print('\n=== Done ===')
```

### Deploy command
```bash
echo "<base64>" | base64 -d | sudo python3
```

Generate base64: `base64 -w 0 script.py`

## After deploying PHP files
Always syntax-check:
```bash
php -l /var/www/edfundo-pay/path/to/file.php
```

Always clear relevant caches:
```bash
sudo php /var/www/edfundo-pay/artisan view:clear    # after blade changes
sudo php /var/www/edfundo-pay/artisan config:clear  # after config changes
```

## NEVER use artisan tinker
psysh throws permission errors. Use PHP bootstrap one-liner instead:
```bash
cat << 'EOF' | sudo php
<?php
define('LARAVEL_START', microtime(true));
require '/var/www/edfundo-pay/vendor/autoload.php';
$app = require_once '/var/www/edfundo-pay/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Laravel code here
EOF
```

## Testing emails
```bash
cat << 'EOF' | sudo php
<?php
define('LARAVEL_START', microtime(true));
require '/var/www/edfundo-pay/vendor/autoload.php';
$app = require_once '/var/www/edfundo-pay/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$payment = App\Models\AppUserPayment::with('invoice.merchant')
    ->whereNotNull('customer_email')
    ->latest()
    ->first();
Mail::to('simon@edfundo.com')->send(new App\Mail\PaymentReceipt($payment));
echo "Sent!\n";
EOF
```
