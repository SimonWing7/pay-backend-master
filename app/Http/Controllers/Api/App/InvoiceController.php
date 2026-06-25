<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentInitiationResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {
    }

    public function getInvoice(string $uuid): JsonResponse
    {
        $invoice = \App\Models\Invoice::where('uuid', $uuid)
            ->with(['merchant', 'consumer', 'invoiceDetails.product'])
            ->first();

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found',
            ], 404);
        }

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            return response()->json([
                'message' => 'This invoice is not available. The merchant account is inactive.',
            ], 403);
        }

        return response()->json(new InvoiceResource($invoice));
    }

    public function initiatePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'invoice_uuid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $invoice = \App\Models\Invoice::where('uuid', $request->input('invoice_uuid'))->first();

        if (!$invoice) {
            return response()->json([
                'message' => 'Invoice not found',
            ], 404);
        }

        if (!$invoice->merchant || !$invoice->merchant->is_active) {
            return response()->json([
                'message' => 'Payment cannot be initiated. The merchant account is inactive.',
            ], 403);
        }

        $appUserId = $request->user()->id;
        $payment = $this->invoiceService->initiateByUuid($request->input('invoice_uuid'), $appUserId);

        if (!$payment) {
            return response()->json([
                'message' => 'Failed to initiate payment',
            ], 500);
        }

        $paymentResource = new PaymentInitiationResource($payment);
        return response()->json($paymentResource);
    }
}

