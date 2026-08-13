<?php

namespace App\Http\Controllers\Api;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Consumer;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Merchant;
use App\Models\AppUserPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Cache\LockTimeoutException;

class MerchantApiController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/v1/payment-links
    // Create a new payment link (invoice) programmatically.
    // -------------------------------------------------------------------------
    public function createPaymentLink(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchantFromKey');

        $validator = Validator::make($request->all(), [
            'amount'           => 'required|numeric|min:0.01|max:50000',
            'description'      => 'required|string|max:255',
            'reference'        => 'nullable|string|max:100',
            'return_url'       => 'nullable|url|max:2048',
            'cancel_url'       => 'nullable|url|max:2048',
            'customer'         => 'nullable|array',
            'customer.name'    => 'nullable|string|max:255',
            'customer.email'   => 'nullable|email|max:255',
            'customer.mobile'            => 'nullable|string|max:50',
            'custom_fields'            => 'nullable|array',
            'custom_fields.*.label'    => 'required|string|max:100',
            'custom_fields.*.required' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // If a reference is provided, guard the whole check-then-create
        // sequence with a lock keyed on merchant+reference. Without this,
        // two concurrent requests for the same reference can both pass the
        // "does it exist yet" check before either has committed its insert,
        // producing two invoices for what should be one idempotent link.
        if (!empty($data['reference'])) {
            try {
                return Cache::lock("payment-link-create:{$merchant->id}:{$data['reference']}", 10)
                    ->block(5, fn () => $this->createOrReusePaymentLink($merchant, $data));
            } catch (LockTimeoutException $e) {
                return response()->json([
                    'message' => 'Another request for this reference is already being processed. Please retry.',
                ], 409);
            }
        }

        return $this->createOrReusePaymentLink($merchant, $data);
    }

    private function createOrReusePaymentLink(Merchant $merchant, array $data): JsonResponse
    {
        // If an active (not failed/archived) invoice already exists for
        // this reference, reuse it instead of creating a duplicate.
        // Prevents duplicate payment links from page refreshes/retried
        // requests, and — more importantly — stops a customer being asked
        // to pay again for a reference that's already been paid.
        if (!empty($data['reference'])) {
            $existing = Invoice::where('merchant_id', $merchant->id)
                ->where('reference', $data['reference'])
                ->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Paid])
                ->with(['consumer', 'invoiceDetails'])
                ->latest()
                ->first();

            if ($existing) {
                return response()->json([
                    'data' => $this->formatPaymentLink($existing),
                ], 201);
            }
        }

        // Resolve or create consumer if customer details were provided
        $consumer = null;
        if (!empty($data['customer'])) {
            $c       = $data['customer'];
            $email   = $c['email'] ?? null;
            $mobile  = $c['mobile'] ?? null;
            $name    = $c['name'] ?? null;

            if ($email || $mobile) {
                // Look for an existing consumer matching email or mobile
                $consumer = Consumer::where('merchant_id', $merchant->id)
                    ->where(function ($q) use ($email, $mobile) {
                        if ($email)  $q->orWhere('email', $email);
                        if ($mobile) $q->orWhere('mobile_number', $mobile);
                    })
                    ->first();
            }

            if (!$consumer && $name) {
                $consumer = Consumer::create([
                    'merchant_id'   => $merchant->id,
                    'name'          => $name,
                    'email'         => $email,
                    'mobile_number' => $mobile,
                ]);
            }
        }

        // Create the invoice
        $invoice = Invoice::create([
            'merchant_id' => $merchant->id,
            'consumer_id' => $consumer?->id,
            'total_fee'   => $data['amount'],
            'status'      => InvoiceStatus::Draft,
            'return_url'  => $data['return_url'] ?? null,
            'cancel_url'  => $data['cancel_url'] ?? null,
            'reference'    => $data['reference'] ?? null,
            'custom_fields' => !empty($data['custom_fields']) ? $data['custom_fields'] : null,
        ]);

        // Create a single line-item
        InvoiceDetail::create([
            'invoice_id' => $invoice->id,
            'product_id' => null,
            'title'      => $data['description'],
            'fee'        => $data['amount'],
        ]);

        return response()->json([
            'data' => $this->formatPaymentLink($invoice->load(['consumer', 'invoiceDetails'])),
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/payment-links/{uuid}
    // Retrieve a single payment link by its UUID.
    // -------------------------------------------------------------------------
    public function getPaymentLink(Request $request, string $uuid): JsonResponse
    {
        $merchant = $request->attributes->get('merchantFromKey');

        $invoice = Invoice::where('uuid', $uuid)
            ->where('merchant_id', $merchant->id)
            ->with(['consumer', 'invoiceDetails', 'appUserPayments'])
            ->first();

        if (!$invoice) {
            return response()->json(['message' => 'Payment link not found.'], 404);
        }

        return response()->json([
            'data' => $this->formatPaymentLink($invoice),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/payments
    // List all payments for this merchant, newest first. Supports filters.
    // Query params: status (pending|complete|failed), date_from, date_to, per_page (max 100)
    // -------------------------------------------------------------------------
    public function listPayments(Request $request): JsonResponse
    {
        $merchant = $request->attributes->get('merchantFromKey');

        $query = AppUserPayment::with(['invoice.consumer', 'invoice.invoiceDetails'])
            ->whereHas('invoice', function ($q) use ($merchant) {
                $q->where('merchant_id', $merchant->id);
            })
            ->orderBy('created_at', 'desc');

        // Status filter
        if ($request->has('status')) {
            $statusMap = [
                'pending'  => PaymentStatus::Initiated,
                'complete' => PaymentStatus::Complete,
                'failed'   => PaymentStatus::Failed,
            ];
            $statusKey = strtolower($request->query('status'));
            if (isset($statusMap[$statusKey])) {
                $query->where('status', $statusMap[$statusKey]);
            }
        }

        // Date filters
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->query('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->query('date_to'));
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->map(fn($p) => $this->formatPayment($p)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * The hosted payment page's customer detail fields already fall back to
     * these query params via request() — that part was built previously,
     * but nothing ever populated them. Appending them here means a customer
     * whose name/email/mobile the merchant already captured (e.g. from
     * their own checkout) doesn't have to retype it on the hosted page.
     */
    private function buildPaymentUrl(Invoice $invoice): string
    {
        $url = url('/invoice/' . $invoice->uuid);

        $params = array_filter([
            'customer_name'   => $invoice->consumer?->name,
            'customer_email'  => $invoice->consumer?->email,
            'customer_mobile' => $invoice->consumer?->mobile_number,
        ]);

        return $params ? $url . '?' . http_build_query($params) : $url;
    }

    private function formatPaymentLink(Invoice $invoice): array
    {
        $latestPayment = $invoice->appUserPayments?->sortByDesc('created_at')->first();
        $paidAt = null;

        if ($latestPayment && $latestPayment->status === PaymentStatus::Complete) {
            $paidAt = $latestPayment->updated_at?->toIso8601String();
        }

        return [
            'id'          => $invoice->uuid,
            'payment_url' => $this->buildPaymentUrl($invoice),
            'amount'      => (float) $invoice->total_fee,
            'currency'    => 'AED',
            'description' => $invoice->invoiceDetails->first()?->title,
            'status'      => $this->mapInvoiceStatus($invoice->status),
            'customer'    => $invoice->consumer ? [
                'name'   => $invoice->consumer->name,
                'email'  => $invoice->consumer->email,
                'mobile' => $invoice->consumer->mobile_number,
            ] : null,
            'reference'     => $invoice->reference,
            'custom_fields' => $invoice->custom_fields ?? [],
            'return_url' => $invoice->return_url,
            'cancel_url' => $invoice->cancel_url,
            'paid_at'    => $paidAt,
            'created_at' => $invoice->created_at->toIso8601String(),
        ];
    }

    private function formatPayment(AppUserPayment $payment): array
    {
        $invoice = $payment->invoice;

        return [
            'id'              => $payment->id,
            'payment_link_id' => $invoice?->uuid,
            'amount'          => (float) ($invoice?->total_fee ?? 0),
            'currency'        => 'AED',
            'description'     => $invoice?->invoiceDetails->first()?->title,
            'status'          => $this->mapPaymentStatus($payment->status),
            'customer'        => $invoice?->consumer ? [
                'name'   => $invoice->consumer->name,
                'email'  => $invoice->consumer->email,
                'mobile' => $invoice->consumer->mobile_number,
            ] : null,
            'reference'       => $invoice?->reference,
            'paid_at'         => $payment->status === PaymentStatus::Complete
                ? $payment->updated_at?->toIso8601String()
                : null,
            'created_at'      => $payment->created_at->toIso8601String(),
        ];
    }

    private function mapInvoiceStatus(InvoiceStatus $status): string
    {
        return match ($status) {
            InvoiceStatus::Draft    => 'pending',
            InvoiceStatus::Paid     => 'paid',
            InvoiceStatus::Failed   => 'failed',
            InvoiceStatus::Archived => 'archived',
        };
    }

    private function mapPaymentStatus(PaymentStatus $status): string
    {
        return match ($status) {
            PaymentStatus::Initiated => 'pending',
            PaymentStatus::Complete  => 'complete',
            PaymentStatus::Failed    => 'failed',
        };
    }
}
