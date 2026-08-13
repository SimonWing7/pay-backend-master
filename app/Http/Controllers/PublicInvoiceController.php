<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\AppUserPayment;
use App\Models\Invoice;
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

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            abort(403, 'This invoice is not available. The merchant account is inactive.');
        }

        return view('public.invoice', compact('invoice'));
    }

    public function pay(string $uuid, LeanService $lean, Request $request): RedirectResponse|View
    {
        $invoice = Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->firstOrFail();

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            abort(403, 'This invoice is not available. The merchant account is inactive.');
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

        if ($invoice) {
            $isPaid   = $invoice->status === InvoiceStatus::Paid;
            $isFailed = $invoice->status === InvoiceStatus::Failed;

            if ($isPaid && $invoice->return_url) {
                $redirectUrl = $this->appendQueryParams($invoice->return_url, [
                    'status'          => 'paid',
                    'payment_link_id' => $invoice->uuid,
                ]);
                return redirect($redirectUrl);
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
            'leanAppToken', 'leanCustomerId', 'leanCustomerToken', 'leanSandbox'
        ));
    }

    private function appendQueryParams(string $url, array $params): string
    {
        $parsed   = parse_url($url);
        $existing = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $existing);
        }
        $merged = array_merge($existing, $params);

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        if (!empty($parsed['port'])) $base .= ':' . $parsed['port'];
        if (!empty($parsed['path'])) $base .= $parsed['path'];

        return $base . '?' . http_build_query($merged);
    }
}
