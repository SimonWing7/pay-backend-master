<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
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
        $merchantId = $request->user()->id;
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $payments = $this->paymentService->getAll($merchantId, $filters, $sortBy, $sortDir, $perPage);
        return view('merchant.payments.index', compact('payments'));
    }

    public function exportCsv(Request $request): Response
    {
        $merchantId = $request->user()->id;
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $payments = $this->paymentService->getAllForExport($merchantId, $filters, $sortBy, $sortDir);
        $filename = 'payments-' . now()->format('Y-m-d') . '.csv';

        // UTF-8 BOM so Excel/Numbers opens the file with correct encoding
        $csv  = "\xEF\xBB\xBF";
        $csv .= "Payment ID,Initiated Date,Completed Date,Customer Name,Customer Email,Customer Mobile,Payment Link Title,Reference,Amount (AED),Status,Lean Reference,Payment Link ID\n";

        foreach ($payments as $payment) {
            $initiated = $payment->created_at->format('d/m/Y H:i');
            $completed = $payment->flow_success_at?->format('d/m/Y H:i') ?? '';
            $consumer  = $payment->invoice->consumer ?? null;

            // Open links: use details submitted on the payment page
            // Consumer-linked invoices (API): use the consumer record
            $name   = $payment->customer_name   ?? $consumer?->name          ?? '';
            $email  = $payment->customer_email  ?? $consumer?->email         ?? '';
            $mobile = $payment->customer_mobile ?? $consumer?->mobile_number ?? '';

            $title     = $payment->invoice->invoiceDetails->first()?->title ?? '';
            $reference = $payment->invoice->reference ?? '';
            $amount    = number_format($payment->invoice->total_fee ?? 0, 2);
            $status    = $payment->status->label();
            $leanRef   = $payment->lean_payment_intent_id ?? '';
            $linkId    = $payment->invoice->uuid ?? '';

            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '"' . '"', (string) $v) . '"',
                [$payment->id, $initiated, $completed, $name, $email, $mobile, $title, $reference, $amount, $status, $leanRef, $linkId]
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $payment = $this->paymentService->getById($id, $merchantId);

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        return view('merchant.payments.show', compact('payment'));
    }
}

