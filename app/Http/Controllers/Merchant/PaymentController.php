<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
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

