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

        $csv = "Date,Individual,Payment Link Title,Reference,Amount (AED),Status\n";
        foreach ($payments as $payment) {
            $date = $payment->created_at->format('d/m/Y H:i');
            $individual = $payment->invoice->consumer->name ?? '—';
            $title = $payment->invoice->invoiceDetails->first()?->title ?? '—';
            $reference = $payment->invoice->reference ?? '';
            $amount = number_format($payment->invoice->total_fee ?? 0, 2);
            $status = $payment->status->label();

            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v) . '"',
                [$date, $individual, $title, $reference, $amount, $status]
            )) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
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

