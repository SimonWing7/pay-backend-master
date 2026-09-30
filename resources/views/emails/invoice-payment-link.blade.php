<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Request</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

@php
    $merchant = $invoice->merchant;
    $brandName = $merchant->merchant_trading_name ?: $merchant->name;
    $title = $invoice->invoiceDetails->first()->title ?? 'Payment';
    $paymentUrl = route('public.invoice.show', $invoice->uuid);
@endphp

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">

  <tr>
    <td align="center" style="padding-bottom:24px;">
      <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
    </td>
  </tr>

  <tr>
    <td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center" style="padding-bottom:8px;">
          <h1 style="margin:0;font-size:22px;font-weight:700;color:#1A1A2E;">Payment Request from {{ $brandName }}</h1>
        </td></tr>
        <tr><td align="center" style="padding-bottom:32px;">
          <p style="margin:0;font-size:15px;color:#6b7280;line-height:1.5;">Hi {{ $invoice->consumer->name ?? 'there' }}, {{ $brandName }} has sent you a payment request@if($invoice->consumer->student_name ?? null) for {{ $invoice->consumer->student_name }}@endif.</p>
        </td></tr>
      </table>

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
        <tr><td align="center" style="background:#ece8fc;border-radius:8px;padding:20px;">
          <p style="margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">{{ $title }}</p>
          <p style="margin:0;font-size:36px;font-weight:700;color:#3d01bd;">AED {{ number_format($invoice->total_fee, 2) }}</p>
        </td></tr>
      </table>

      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td style="background:#3d01bd;border-radius:10px;padding:20px;text-align:center;">
            <a href="{{ $paymentUrl }}"
               style="display:inline-block;background:#ffffff;color:#3d01bd;font-weight:700;font-size:15px;text-decoration:none;padding:14px 32px;border-radius:6px;">
              Pay Now
            </a>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  <tr>
    <td align="center" style="padding-top:24px;">
      <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.8;">
        Payments processed securely by <strong style="color:#6b7280;">Edfundo Pay</strong> on behalf of {{ $brandName }}<br>
        <a href="https://www.edfundo.com" style="color:#3d01bd;text-decoration:none;">www.edfundo.com</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
