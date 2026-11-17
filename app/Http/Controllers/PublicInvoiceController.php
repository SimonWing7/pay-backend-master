<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
use App\Models\LeanBank;
use App\Services\LeanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicInvoiceController extends Controller
{
    public function show(string $uuid): View
    {
        $invoice = Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->firstOrFail();

        if (!$invoice->merchant || !$invoice->merchant->acceptsPayments()) {
            abort(403, 'This invoice is not available. This merchant is not able to take payments right now.');
        }

        $liveBanks = LeanBank::where('is_available', true)->orderBy('name')->get(['name']);

        return view('public.invoice', compact('invoice', 'liveBanks'));
    }

    public function pay(string $uuid, LeanService $lean, Request $request): RedirectResponse|View
    {
        $invoice = Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->firstOrFail();

        if (!$invoice->merchant || !$invoice->merchant->acceptsPayments()) {
            abort(403, 'This invoice is not available. This merchant is not able to take payments right now.');
        }

        if ($invoice->status !== InvoiceStatus::Draft) {
            return redirect()->route('public.invoice.show', $uuid)
                ->with('error', 'This invoice cannot be paid — it has already been paid or is no longer active.');
        }

        $rules = [
            'customer_name'   => 'required|string|max:255',
            'customer_email'  => 'required|email|max:255',
            'customer_mobile' => 'required|string|max:50',
            'custom_fields'   => 'nullable|array',
        ];

        if ($invoice->custom_fields) {
            foreach ($invoice->custom_fields as $field) {
                $key = 'custom_fields.' . $field['label'];
                $rules[$key] = !empty($field['required']) ? 'required|string|max:500' : 'nullable|string|max:500';
            }
        }

        $validated = $request->validate($rules);

        $result = $lean->createPaymentIntent($invoice, $validated['customer_email']);

        if (!$result['success']) {
            Log::error('Lean payment intent creation failed', [
                'invoice_uuid' => $uuid,
                'error'        => $result['error'],
            ]);

            return redirect()->route('public.invoice.show', $uuid)
                ->with('error', 'Payment could not be initiated. Please try again or contact the merchant.');
        }

        $paymentIntentId = $result['payment_intent_id'];
        $leanCustomerId  = $result['lean_customer_id'] ?? null;

        $leanCustomerToken = '';
        if ($leanCustomerId) {
            try {
                $leanCustomerToken = $lean->getCustomerToken($leanCustomerId);
            } catch (\RuntimeException $e) {
                Log::warning('Lean: could not generate customer-scoped token', [
                    'invoice_uuid'     => $uuid,
                    'lean_customer_id' => $leanCustomerId,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        $payment = AppUserPayment::create([
            'app_user_id'            => null,
            'invoice_id'             => $invoice->id,
            'payment_channel'        => 'web',
            'status'                 => PaymentStatus::Initiated,
            'customer_name'          => $validated['customer_name'] ?? null,
            'customer_email'         => $validated['customer_email'] ?? null,
            'customer_mobile'        => $validated['customer_mobile'] ?? null,
            'custom_field_values'    => !empty($validated['custom_fields']) ? $validated['custom_fields'] : null,
            'token'                  => Str::uuid()->toString(),
            'lean_payment_intent_id' => $paymentIntentId,
            'lean_metadata'          => array_merge(
                $result['data'] ?? [],
                ['lean_customer_id' => $leanCustomerId]
            ),
        ]);

        Log::info('Lean payment initiated', [
            'invoice_uuid'      => $uuid,
            'payment_id'        => $payment->id,
            'payment_intent_id' => $paymentIntentId,
            'lean_customer_id'  => $leanCustomerId,
        ]);

        return view('public.payment-process', [
            'invoice'           => $invoice,
            'payment'           => $payment,
            'paymentIntentId'   => $paymentIntentId,
            'leanAppToken'      => $lean->getAppToken(),
            'leanSandbox'       => $lean->isSandbox(),
            'leanCustomerToken' => $leanCustomerToken,
        ]);
    }

    public function paymentReturn(Request $request, LeanService $lean): View|RedirectResponse
    {
        $intentId = $request->query('intent_id');
        $status   = $request->query('status');

        $payment = null;
        $invoice = null;

        if ($intentId) {
            $payment = AppUserPayment::where('lean_payment_intent_id', $intentId)
                ->with('invoice.merchant')
                ->first();

            if ($payment) {
                $invoice = $payment->invoice;
            }
        }

        $merchantReturnUrl = null;

        if ($invoice) {
            $isPaid   = $invoice->status === InvoiceStatus::Paid;
            $isFailed = $invoice->status === InvoiceStatus::Failed;

            // The customer is sent back to the merchant from the page below
            // (a button plus an automatic redirect after a few seconds), so
            // the reward offer still gets seen first. That applies as soon as
            // the payment is submitted, not only once Lean has confirmed it:
            // the bank's confirmation often arrives after the customer does,
            // and they would otherwise be left with no way back. Merchants
            // verify the status themselves, so an unconfirmed payment is
            // reported as "pending" rather than "paid".
            $submitted = $isPaid
                || $status === 'success'
                || $payment?->status === PaymentStatus::Complete;

            if ($submitted && $invoice->return_url && !Invoice::isScriptUrl($invoice->return_url)) {
                $merchantReturnUrl = $this->appendQueryParams($invoice->return_url, [
                    'status'          => $isPaid ? 'paid' : 'pending',
                    'payment_link_id' => $invoice->uuid,
                ]);
            }

            if ($isFailed && $invoice->cancel_url) {
                $redirectUrl = $this->appendQueryParams($invoice->cancel_url, [
                    'status'          => 'failed',
                    'payment_link_id' => $invoice->uuid,
                ]);
                return redirect($redirectUrl);
            }
        }

        $leanCustomerId    = null;
        $leanCustomerToken = '';

        if ($payment && !empty($payment->lean_metadata['lean_customer_id'])) {
            $leanCustomerId = $payment->lean_metadata['lean_customer_id'];
            try {
                $leanCustomerToken = $lean->getCustomerToken($leanCustomerId);
            } catch (\RuntimeException $e) {
                Log::warning('Lean: could not generate customer token on return page', [
                    'intent_id'        => $intentId,
                    'lean_customer_id' => $leanCustomerId,
                    'error'            => $e->getMessage(),
                ]);
            }
        }

        $leanAppToken = $lean->getAppToken();
        $leanSandbox  = $lean->isSandbox();

        return view('public.payment-return', compact(
            'payment', 'invoice', 'status', 'intentId',
            'leanAppToken', 'leanCustomerId', 'leanCustomerToken', 'leanSandbox',
            'merchantReturnUrl'
        ));
    }

    private function appendQueryParams(string $url, array $params): string
    {
        return Invoice::appendQuery($url, $params);
    }
}
