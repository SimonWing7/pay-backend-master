<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edfundo Pay fees</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php
    $tz = config('billing.timezone', 'Asia/Dubai');
    $amount = 'AED ' . number_format($statement->fee_amount, 2);
    $statementUrl = route('merchant.billing.statement', $statement->number);
    $billingUrl = route('merchant.billing.index');
    $needsAction = in_array($kind, ['retry', 'failed', 'awaiting_card'], true);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

    @if($kind === 'paid')
      <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#1A1A2E;">Payment received</h1>
      <p style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.6;">
        Thank you. We've charged <strong>{{ $amount }}</strong> for your Edfundo Pay fees for {{ $statement->periodLabel() }}
        (statement {{ $statement->number }}). You can view or print the statement any time.
      </p>
    @elseif($kind === 'retry')
      <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#1A1A2E;">We couldn't charge your card</h1>
      <p style="margin:0 0 12px;font-size:14px;color:#6b7280;line-height:1.6;">
        We tried to collect <strong>{{ $amount }}</strong> for your Edfundo Pay fees for {{ $statement->periodLabel() }}
        (statement {{ $statement->number }}), but the payment didn't go through.
        @if($statement->failure_message) Reason given: {{ $statement->failure_message }}. @endif
      </p>
      <p style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.6;">
        We'll try again on <strong>{{ $retryAt?->copy()->setTimezone($tz)->format('j F Y') }}</strong>.
        To make sure it succeeds, please check your card or add a new one.
      </p>
    @elseif($kind === 'failed')
      <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#1A1A2E;">Your fees payment failed</h1>
      <p style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.6;">
        We weren't able to collect <strong>{{ $amount }}</strong> for your Edfundo Pay fees for {{ $statement->periodLabel() }}
        (statement {{ $statement->number }}) after several attempts.
        @if($statement->failure_message) Reason given: {{ $statement->failure_message }}. @endif
        Please add a working card, or reply to this email so we can arrange payment another way.
      </p>
    @else
      <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#1A1A2E;">Please add a card</h1>
      <p style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.6;">
        We're due to collect <strong>{{ $amount }}</strong> for your Edfundo Pay fees for {{ $statement->periodLabel() }}
        (statement {{ $statement->number }}), but there's no card registered on your account. Add one and we'll collect the payment automatically.
      </p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center">
        <a href="{{ $needsAction ? $billingUrl : $statementUrl }}" style="display:inline-block;background:#3d01bd;color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:6px;">{{ $needsAction ? 'Update my card' : 'View statement' }}</a>
      </td></tr>
    </table>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
