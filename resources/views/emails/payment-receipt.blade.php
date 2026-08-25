<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fa;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" style="max-width:580px;" cellpadding="0" cellspacing="0">

  {{-- Brand header --}}
  <tr>
    <td align="center" style="padding-bottom:24px;">
      <span style="font-size:22px;font-weight:700;color:#1A1A2E;letter-spacing:-0.5px;">Edfundo <span style="color:#3d01bd;">Pay</span></span>
    </td>
  </tr>

  {{-- Card --}}
  <tr>
    <td style="background:#ffffff;border-radius:12px;padding:40px;box-shadow:0 2px 8px rgba(0,0,0,0.06);">

      {{-- Success icon + heading --}}
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td align="center" style="padding-bottom:20px;">
          <div style="width:64px;height:64px;border-radius:50%;background:#e8f9f3;text-align:center;font-size:28px;color:#3DBA8C;font-weight:700;line-height:64px;display:inline-block;">&#10003;</div>
        </td></tr>
        <tr><td align="center" style="padding-bottom:8px;">
          <h1 style="margin:0;font-size:24px;font-weight:700;color:#1A1A2E;">Payment Confirmed</h1>
        </td></tr>
        <tr><td align="center" style="padding-bottom:32px;">
          <p style="margin:0;font-size:15px;color:#6b7280;line-height:1.5;">Thank you, {{ $payment->customer_name }}. Your payment has been successfully processed.</p>
        </td></tr>
      </table>

      {{-- Amount box --}}
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
        <tr><td align="center" style="background:#ece8fc;border-radius:8px;padding:20px;">
          <p style="margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;font-weight:600;">Amount Paid</p>
          <p style="margin:0;font-size:36px;font-weight:700;color:#3d01bd;">AED {{ number_format($payment->invoice->total_fee, 2) }}</p>
        </td></tr>
      </table>

      {{-- Payment details --}}
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr><td colspan="2" style="padding-bottom:12px;border-bottom:2px solid #f0f0f0;">
          <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;">Payment Details</p>
        </td></tr>

        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;width:40%;vertical-align:top;">Paid To</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $payment->invoice->merchant->name ?? '&mdash;' }}</td>
        </tr>

        @if($payment->invoice->reference)
        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;vertical-align:top;">Reference</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $payment->invoice->reference }}</td>
        </tr>
        @endif

        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;vertical-align:top;">Date</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ now('Asia/Dubai')->format('d M Y, H:i') }} (GST)</td>
        </tr>

        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;vertical-align:top;">Payer</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $payment->customer_name }}</td>
        </tr>

        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;vertical-align:top;">Email</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $payment->customer_email }}</td>
        </tr>

        @if($payment->customer_mobile)
        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;vertical-align:top;">Mobile</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $payment->customer_mobile }}</td>
        </tr>
        @endif

        <tr>
          <td style="padding:12px 0;font-size:14px;color:#6b7280;vertical-align:top;">Transaction ID</td>
          <td style="padding:12px 0;font-size:12px;color:#6b7280;font-family:'Courier New',monospace;text-align:right;vertical-align:top;word-break:break-all;">{{ $payment->lean_payment_intent_id }}</td>
        </tr>
      </table>

      @php
        $customFields = [];
        $rawFields = $payment->custom_field_values;
        if (!empty($rawFields)) {
            $customFields = is_array($rawFields) ? $rawFields : (json_decode($rawFields, true) ?? []);
        }
      @endphp
      @if(count($customFields) > 0)
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr><td colspan="2" style="padding-bottom:12px;border-bottom:2px solid #f0f0f0;">
          <p style="margin:0;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.06em;">Additional Details</p>
        </td></tr>
        @foreach($customFields as $label => $value)
        @if(!empty($value))
        <tr>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#6b7280;width:40%;vertical-align:top;">{{ $label }}</td>
          <td style="padding:12px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;font-weight:500;text-align:right;vertical-align:top;">{{ $value }}</td>
        </tr>
        @endif
        @endforeach
      </table>
      @endif

      {{-- AED 50 Reward CTA --}}
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
          <td style="background:#3d01bd;border-radius:10px;padding:28px 24px;text-align:center;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:rgba(255,255,255,0.7);text-transform:uppercase;letter-spacing:0.08em;">Exclusive Offer</p>
            <p style="margin:0 0 10px;font-size:20px;font-weight:700;color:#ffffff;line-height:1.3;">Claim Your AED 50 Reward</p>
            <p style="margin:0 0 22px;font-size:14px;color:rgba(255,255,255,0.85);line-height:1.6;">Download the Edfundo app and start a subscription to claim your AED 50 reward &mdash; as a thank you for paying with Edfundo Pay.</p>
            <a href="https://edfundo.com/edfundo-pay-rewards/?ref={{ $payment->invoice->merchant->id ?? '' }}&amp;utm_source=edfundo_pay&amp;utm_medium=payment_receipt"
               style="display:inline-block;background:#ffffff;color:#3d01bd;font-weight:700;font-size:14px;text-decoration:none;padding:13px 30px;border-radius:6px;letter-spacing:0.01em;">
              Find Out How to Claim &rarr;
            </a>
          </td>
        </tr>
      </table>

      {{-- Note --}}
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="background:#f9fafb;border-radius:8px;padding:16px;font-size:13px;color:#6b7280;line-height:1.6;">
          Please keep this email as your proof of payment. For any questions about this payment, please contact the merchant directly.
        </td></tr>
      </table>

    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td align="center" style="padding-top:24px;">
      <p style="margin:0;font-size:12px;color:#9ca3af;line-height:1.8;">
        Payments processed securely by <strong style="color:#6b7280;">Edfundo Pay</strong><br>
        <a href="https://www.edfundo.com" style="color:#3d01bd;text-decoration:none;">www.edfundo.com</a>
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>

</body>
</html>
