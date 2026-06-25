<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
use App\Services\NymCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PublicInvoiceController extends Controller
{
    public function show(string $uuid): View
    {
        $invoice = Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->firstOrFail();

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            abort(403, 'This invoice is not available. The merchant account is inactive.');
        }

        return view('public.invoice', compact('invoice'));
    }

    /**
     * Initiate a web-based Open Finance payment for an invoice.
     * Creates an AppUserPayment record (with app_user_id = null for web payments),
     * calls NymCard to initiate the payment, then either:
     *   - Redirect flow: redirects the customer to NymCard's hosted page
     *   - Embedded flow: redirects to our payment-process page with the sdkToken
     */
    public function pay(string $uuid, NymCardService $nymCard): RedirectResponse|View
    {
        $invoice = Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->firstOrFail();

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            abort(403, 'This invoice is not available. The merchant account is inactive.');
        }

        if ($invoice->status !== InvoiceStatus::Draft) {
            return redirect()->route('public.invoice.show', $uuid)
                ->with('error', 'This invoice cannot be paid — it has already been paid or failed.');
        }

        try {
            $paymentData = $nymCard->buildWebPaymentRequest($invoice);
        } catch (\Exception $e) {
            Log::error('Failed to build web payment request', [
                'invoice_uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('public.invoice.show', $uuid)
                ->with('error', 'Payment could not be initiated. Please contact the merchant.');
        }

        $result = $nymCard->initiatePayment($paymentData);

        if (!$result['success']) {
            Log::error('NymCard initiate payment failed for web flow', [
                'invoice_uuid' => $uuid,
                'error' => $result['error'],
            ]);

            return redirect()->route('public.invoice.show', $uuid)
                ->with('error', 'Payment could not be initiated. Please try again or contact the merchant.');
        }

        $data = $result['data'];

        // Create the payment record (no AppUser for web payments)
        $payment = AppUserPayment::create([
            'app_user_id' => null,
            'invoice_id' => $invoice->id,
            'payment_channel' => 'web',
            'status' => PaymentStatus::Initiated,
            'nymcard_resource_id' => $data['resourceId'] ?? null,
            'nymcard_user_id' => $data['userId'] ?? null,
            'nymcard_token' => $data['token'] ?? null,
            'nymcard_metadata' => $data,
        ]);

        // Redirect flow: NymCard returns a hosted redirectUri
        if (!empty($data['redirectUri'])) {
            Log::info('NymCard redirect flow initiated', [
                'invoice_uuid' => $uuid,
                'payment_id' => $payment->id,
                'redirect_uri' => $data['redirectUri'],
            ]);

            return redirect($data['redirectUri']);
        }

        // Embedded flow: NymCard returns an sdkToken for the Web SDK
        if (!empty($data['token'])) {
            Log::info('NymCard embedded flow initiated', [
                'invoice_uuid' => $uuid,
                'payment_id' => $payment->id,
            ]);

            return view('public.payment-process', [
                'invoice' => $invoice,
                'payment' => $payment,
                'sdkToken' => $data['token'],
                'resourceId' => $data['resourceId'] ?? null,
            ]);
        }

        // Unexpected response shape
        Log::error('NymCard response missing token and redirectUri', [
            'invoice_uuid' => $uuid,
            'data' => $data,
        ]);

        return redirect()->route('public.invoice.show', $uuid)
            ->with('error', 'Payment could not be initiated. Please try again.');
    }

    /**
     * Handle the return from NymCard's redirect flow.
     * NymCard redirects back here after the customer completes (or cancels) payment.
     * The actual status update comes via webhook; this page just shows a waiting/confirmation screen.
     */
    public function paymentReturn(Request $request): View
    {
        $resourceId = $request->query('resourceId');
        $status = $request->query('status');

        $payment = null;
        $invoice = null;

        if ($resourceId) {
            $payment = AppUserPayment::where('nymcard_resource_id', $resourceId)
                ->with('invoice.merchant')
                ->first();

            if ($payment) {
                $invoice = $payment->invoice;
            }
        }

        return view('public.payment-return', compact('payment', 'invoice', 'status', 'resourceId'));
    }
}
