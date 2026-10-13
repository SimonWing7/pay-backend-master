<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Edfundo Pay fees</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php
    $tz = config('billing.timezone', 'Asia/Dubai');
    $chargeDate = $statement->charge_due_at->copy()->setTimezone($tz)->format('j F Y');
    $statementUrl = route('merchant.billing.statement', $statement->number);
    $billingUrl = route('merchant.billing.index');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:#1A1A2E;">Your Edfundo Pay fees for {{ $statement->periodLabel() }}</h1>
    <p style="margin:0 0 24px;font-size:13px;color:#9ca3af;">Statement {{ $statement->number }}</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Completed payments processed</td>
        <td align="right" style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;">AED {{ number_format($statement->volume, 2) }}</td>
      </tr>
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">Edfundo fee rate</td>
        <td align="right" style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;">{{ rtrim(rtrim(number_format($statement->fee_percentage, 2), '0'), '.') }}%</td>
      </tr>
      <tr>
        <td style="padding:14px 0 0;font-size:15px;font-weight:700;color:#111827;">Total due</td>
        <td align="right" style="padding:14px 0 0;font-size:20px;font-weight:700;color:#3d01bd;">AED {{ number_format($statement->fee_amount, 2) }}</td>
      </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
      <tr><td style="background:#f9fafb;border-radius:8px;padding:16px;font-size:14px;color:#374151;line-height:1.6;">
        @if($card)
          On <strong>{{ $chargeDate }}</strong> we'll charge <strong>AED {{ number_format($statement->fee_amount, 2) }}</strong>
          to your {{ $card->label() }}. Nothing for you to do. If something looks wrong, reply to this email before then.
        @else
          You don't have a card registered yet. Please add one before <strong>{{ $chargeDate }}</strong>
          so we can collect this payment.
        @endif
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center">
        <a href="{{ $statementUrl }}" style="display:inline-block;background:#3d01bd;color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:6px;">View statement</a>
      </td></tr>
      @unless($card)
      <tr><td align="center" style="padding-top:14px;">
        <a href="{{ $billingUrl }}" style="font-size:14px;color:#3d01bd;text-decoration:none;">Add a card &rarr;</a>
      </td></tr>
      @endunless
    </table>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
