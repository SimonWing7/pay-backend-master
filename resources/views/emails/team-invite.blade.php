<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $kind === 'reset' ? 'Reset your password' : 'Your Edfundo Pay invitation' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php
    $isReset = $kind === 'reset';
    $business = $member->merchant?->merchant_trading_name ?: $member->merchant?->name ?: 'your team';
    $roleLine = \App\Models\MerchantUser::ROLES[$member->role]['description'] ?? '';
    $acceptUrl = route('merchant.team.accept', $token);
    $validFor = $isReset ? \App\Models\MerchantUser::RESET_VALID_HOURS . ' hours' : '7 days';
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    @if($isReset)
      <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">Reset your password</h1>
      <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#374151;">Hi {{ $member->name }}, we received a request to reset your Edfundo Pay password for {{ $business }}. Use the button below to choose a new one.</p>
    @else
      <h1 style="margin:0 0 16px;font-size:20px;font-weight:700;color:#1A1A2E;">Join {{ $business }} on Edfundo Pay</h1>
      <p style="margin:0 0 12px;font-size:15px;line-height:1.6;color:#374151;">Hi {{ $member->name }}, {{ $member->invited_by ?: 'the account owner' }} has invited you to {{ $business }}'s Edfundo Pay account as <strong>{{ $member->roleLabel() }}</strong>.</p>
      @if($roleLine !== '')
      <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#6b7280;">{{ $roleLine }}</p>
      @endif
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;">
      <tr><td style="background:#3d01bd;border-radius:8px;">
        <a href="{{ $acceptUrl }}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;">{{ $isReset ? 'Choose a new password' : 'Accept invitation and set password' }}</a>
      </td></tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;line-height:1.6;color:#9ca3af;">This link works once and expires in {{ $validFor }}. If the button doesn't work, copy this address into your browser:</p>
    <p style="margin:0 0 20px;font-size:12px;line-height:1.5;color:#6b7280;word-break:break-all;">{{ $acceptUrl }}</p>
    <p style="margin:0;font-size:13px;line-height:1.6;color:#9ca3af;">{{ $isReset ? "If you didn't ask for this, you can ignore this email; your password won't change." : "If you weren't expecting this invitation, you can ignore this email." }}</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
