<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments awaiting bank confirmation</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
@php $reviewUrl = route('merchant.payments.index', ['status' => 'review']); @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">
  <tr><td align="center" style="padding-bottom:24px;">
    <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
  </td></tr>
  <tr><td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h1 style="margin:0 0 12px;font-size:20px;font-weight:700;color:#1A1A2E;">
      {{ $payments->count() }} {{ $payments->count() === 1 ? 'payment is' : 'payments are' }} awaiting bank confirmation
    </h1>
    <p style="margin:0 0 20px;font-size:14px;color:#6b7280;line-height:1.6;">
      The customer's bank accepted these payments but hasn't confirmed them, and the payment provider won't send a final status.
      Please check your bank account for each credit below, then confirm or reject it in your dashboard.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
      <tr>
        <td style="padding:8px 0;border-bottom:2px solid #f0f0f0;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;">Order / reference</td>
        <td style="padding:8px 0;border-bottom:2px solid #f0f0f0;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;">Date</td>
        <td align="right" style="padding:8px 0;border-bottom:2px solid #f0f0f0;font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;">Amount (AED)</td>
      </tr>
      @foreach($payments as $payment)
      <tr>
        <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;">{{ $payment->invoice->reference ?? ('#' . $payment->id) }}</td>
        <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;">{{ $payment->created_at->format('d M Y, H:i') }}</td>
        <td align="right" style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:600;">{{ number_format($payment->invoice->total_fee ?? 0, 2) }}</td>
      </tr>
      @endforeach
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
      <tr><td align="center">
        <a href="{{ $reviewUrl }}" style="display:inline-block;background:#3d01bd;color:#ffffff;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:6px;">Review payments</a>
      </td></tr>
    </table>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
