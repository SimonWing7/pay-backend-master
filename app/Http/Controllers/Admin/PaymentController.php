<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
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
        $merchantId = $request->get('merchant_id');
        $filters = [
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $payments = $this->paymentService->getAll($merchantId ?: null, $filters, $sortBy, $sortDir, $perPage);
        $merchants = Merchant::orderBy('name')->get(['id', 'name']);

        return view('admin.payments.index', compact('payments', 'merchants'));
    }

    public function show(int $id): View
    {
        $payment = $this->paymentService->getById($id);

        if (!$payment) {
            abort(404, 'Payment not found');
        }

        return view('admin.payments.show', compact('payment'));
    }
}

