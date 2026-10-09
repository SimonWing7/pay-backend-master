<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppUserPayment;
use App\Models\Merchant;
use App\Models\MerchantEntity;
use App\Models\Product;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function index(Request $request): View
    {
        $merchantId = $request->get('merchant_id');
        $filters = [
            'status' => $request->get('status'),
            'entity_id' => $request->get('entity_id'),
            'product_id' => $request->get('product_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $payments = $this->paymentService->getAll($merchantId ?: null, $filters, $sortBy, $sortDir, $perPage);
        $merchants = Merchant::orderBy('name')->get(['id', 'name']);
        // Labeled with the merchant name since this spans every merchant,
        // not just whichever one might currently be filtered.
        $entities = MerchantEntity::with('merchant')->get()->map(function ($entity) {
            $entity->name = ($entity->merchant->name ?? '—') . ' — ' . $entity->name;
            return $entity;
        });
        $products = Product::with('merchant')->get()->map(function ($product) {
            $product->name = ($product->merchant->name ?? '—') . ' — ' . $product->name;
            return $product;
        });

        return view('admin.payments.index', compact('payments', 'merchants', 'entities', 'products'));
    }

    public function show(int $id): View
    {
        $payment = $this->paymentService->getById($id);

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Manual reconciliation for a payment Lean left at PENDING_WITH_BANK —
     * an admin has checked the merchant's bank account and found the money.
     */
    public function confirm(Request $request, int $id): RedirectResponse
    {
        $payment = AppUserPayment::with('invoice.merchant')->findOrFail($id);

        if (!$payment->isNeedsReview()) {
            return redirect()->route('admin.payments.show', $id)
                ->with('error', 'This payment is not awaiting manual review.');
        }

        $admin = $request->user();
        $this->paymentService->reconcileConfirm($payment, ['type' => 'admin', 'id' => $admin->id, 'name' => $admin->name]);

        return redirect()->route('admin.payments.show', $id)
            ->with('success', "Payment #{$payment->id} confirmed as paid.");
    }

    /**
     * No credit was found in the merchant's bank account for a payment Lean
     * left at PENDING_WITH_BANK — close it out as failed.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $payment = AppUserPayment::with('invoice')->findOrFail($id);

        if (!$payment->isNeedsReview()) {
            return redirect()->route('admin.payments.show', $id)
                ->with('error', 'This payment is not awaiting manual review.');
        }

        $admin = $request->user();
        $this->paymentService->reconcileReject($payment, ['type' => 'admin', 'id' => $admin->id, 'name' => $admin->name]);

        return redirect()->route('admin.payments.show', $id)
            ->with('success', "Payment #{$payment->id} marked as failed.");
    }

    public function exportCsv(Request $request): Response
    {
        $merchantId = $request->get('merchant_id');
        $filters = [
            'status' => $request->get('status'),
            'entity_id' => $request->get('entity_id'),
            'product_id' => $request->get('product_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $payments = $this->paymentService->getAllForExport($merchantId ?: null, $filters, $sortBy, $sortDir);
        $filename = 'payments-' . now()->format('Y-m-d') . '.csv';

        // UTF-8 BOM so Excel/Numbers opens the file with correct encoding
        $csv  = "\xEF\xBB\xBF";
        $csv .= "Payment ID,Initiated Date,Completed Date,Merchant,Customer Name,Customer Email,Customer Mobile,Payment Link Title,Reference,Amount (AED),Status,Lean Reference,Payment Link ID\n";

        foreach ($payments as $payment) {
            $initiated = $payment->created_at->format('d/m/Y H:i');
            $completed = $payment->flow_success_at?->format('d/m/Y H:i') ?? '';
            $consumer  = $payment->invoice->consumer ?? null;

            // Open links: use details submitted on the payment page
            // Consumer-linked invoices (API): use the consumer record
            $name   = $payment->customer_name   ?? $consumer?->name          ?? '';
            $email  = $payment->customer_email  ?? $consumer?->email         ?? '';
            $mobile = $payment->customer_mobile ?? $consumer?->mobile_number ?? '';

            $merchant  = $payment->invoice->merchant->name ?? '';
            $title     = $payment->invoice->invoiceDetails->first()?->title ?? '';
            $reference = $payment->invoice->reference ?? '';
            $amount    = number_format($payment->invoice->total_fee ?? 0, 2);
            $status    = $payment->status->label();
            $leanRef   = $payment->lean_payment_intent_id ?? '';
            $linkId    = $payment->invoice->uuid ?? '';

            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '"' . '"', (string) $v) . '"',
                [$payment->id, $initiated, $completed, $merchant, $name, $email, $mobile, $title, $reference, $amount, $status, $leanRef, $linkId]
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

