<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome to Edfundo Pay</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php
    $business = $merchant->merchant_trading_name ?: $merchant->name;
    $setupUrl = route('merchant.password.reset', ['token' => $token, 'email' => $merchant->email]);
    $days = config('onboarding.welcome_link_days', 7);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">Welcome to Edfundo Pay, {{ $business }}</h1>
    <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Your account is ready. Your login email is <strong>{{ $merchant->email }}</strong>. Choose your password to sign in; there is no temporary password to remember.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
      <tr><td style="background:#3d01bd;border-radius:8px;">
        <a href="{{ $setupUrl }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Set my password</a>
      </td></tr>
    </table>
    <p style="margin:0 0 8px;font-size:14px;line-height:1.6;color:#374151;"><strong>What happens next:</strong> the first time you sign in we'll walk you through a short set-up, one step at a time. It takes about 5 minutes if you have these to hand (and you can save and finish later if you don't):</p>
    <ul style="margin:0 0 20px;padding-left:20px;font-size:14px;line-height:1.7;color:#374151;">
      <li><strong>Your trade licence</strong>: licence number and registered address</li>
      <li><strong>Your business bank details</strong>: IBAN, account number and SWIFT code (from your banking app, a statement or a letter from your bank)</li>
      <li><strong>An authorised contact</strong>: name, role, mobile and email</li>
    </ul>
    <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#9ca3af;">This link works once and expires in {{ $days }} days. If the button doesn't work, copy this address into your browser:</p>
    <p style="margin:0 0 20px;font-size:12px;line-height:1.5;color:#6b7280;word-break:break-all;">{{ $setupUrl }}</p>
    <p style="margin:0;font-size:13px;line-height:1.6;color:#9ca3af;">If the link has expired, use "Forgot password" on the sign-in page, or reply to this email and we'll send a new one.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
