<?php

namespace App\Http\Controllers\Api\App;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    public function getPayments(Request $request): JsonResponse
    {
        $appUserId = $request->user()->id;

        $payments = \App\Models\AppUserPayment::where('app_user_id', $appUserId)
            ->where('status', '!=', PaymentStatus::Initiated->value)
            ->with(['invoice.consumer', 'invoice.invoiceDetails.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => PaymentResource::collection($payments),
        ]);
    }

    public function getPaymentReceipt(Request $request, string $id): JsonResponse
    {
        $appUserId = $request->user()->id;

        // Apply user where query to avoid unauthorized access to receipts
        $payment = \App\Models\AppUserPayment::where('id', $id)
            ->where('app_user_id', $appUserId)
            ->with(['invoice.consumer', 'invoice.invoiceDetails.product'])
            ->first();

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        return response()->json(new PaymentResource($payment));
    }

    /**
     * Handle payment flow success event from SDK
     */
    public function handleFlowSuccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sdk_token' => 'required|string',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payment = $this->paymentService->handleFlowSuccess(
            $request->input('sdk_token'),
            $request->input('payload')
        );

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify that the payment belongs to the authenticated user
        if ($payment->app_user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Flow success event recorded',
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Handle payment flow failure event from SDK
     */
    public function handleFlowFailure(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sdk_token' => 'required|string',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payment = $this->paymentService->handleFlowFailure(
            $request->input('sdk_token'),
            $request->input('payload')
        );

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify that the payment belongs to the authenticated user
        if ($payment->app_user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Flow failure event recorded',
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Handle payment flow done event from SDK
     */
    public function handleFlowDone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sdk_token' => 'required|string',
            'payload' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $payment = $this->paymentService->handleFlowDone(
            $request->input('sdk_token'),
            $request->input('payload')
        );

        if (!$payment) {
            return response()->json([
                'message' => 'Payment not found',
            ], 404);
        }

        // Verify that the payment belongs to the authenticated user
        if ($payment->app_user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'message' => 'Flow done event recorded',
            'payment' => new PaymentResource($payment),
        ]);
    }
}

