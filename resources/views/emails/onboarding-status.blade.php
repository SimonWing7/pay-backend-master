<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edfundo Pay onboarding</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php
    $business = $merchant->merchant_trading_name ?: $merchant->name;
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    @if($kind === 'submitted')
      <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">Onboarding submitted: {{ $business }}</h1>
      <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">{{ $business }} ({{ $merchant->email }}) has finished onboarding and is waiting for review.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
      <tr><td style="background:#3d01bd;border-radius:8px;">
        <a href="{{ route('admin.merchants.show', $merchant->id) }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Review in admin</a>
      </td></tr>
    </table>
    @elseif($kind === 'approved')
      <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">You're live, {{ $business }}</h1>
      <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">We've reviewed your details and your account is approved. You can now send payment links and collect payments.</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
      <tr><td style="background:#3d01bd;border-radius:8px;">
        <a href="{{ route('merchant.dashboard') }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Go to my dashboard</a>
      </td></tr>
    </table>
    @else
      <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">A few changes needed</h1>
      <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Thanks for submitting your details, {{ $business }}. Before we can approve your account we need you to update the following:</p>
      <p style="margin:0 0 20px;padding:14px 16px;background:#fffbeb;border-left:3px solid #f59e0b;font-size:14px;line-height:1.6;color:#92400e;white-space:pre-line;">{{ $note }}</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
      <tr><td style="background:#3d01bd;border-radius:8px;">
        <a href="{{ route('merchant.onboarding.index') }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">Update my details</a>
      </td></tr>
    </table>
    @endif
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
