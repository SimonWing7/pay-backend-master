<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ConsumerService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PublicProductController extends Controller
{
    public function __construct(
        protected ConsumerService $consumerService,
        protected InvoiceService $invoiceService
    ) {
    }

    public function show(string $uuid): View
    {
        $product = Product::where('uuid', $uuid)
            ->where('state', 'active')
            ->with('merchant')
            ->firstOrFail();

        if (!$product->merchant || !$product->merchant->is_active) {
            abort(403, 'This product is not available. The merchant account is inactive.');
        }

        return view('public.product', compact('product'));
    }

    public function store(Request $request, string $uuid): RedirectResponse
    {
        $product = Product::where('uuid', $uuid)
            ->where('state', 'active')
            ->with('merchant')
            ->firstOrFail();

        if (!$product->merchant || !$product->merchant->is_active) {
            abort(403, 'This product is not available. The merchant account is inactive.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'required|email',
            'mobile_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if consumer already exists with this email and merchant_id
        $consumerData = $validator->validated();
        $consumer = \App\Models\Consumer::where('merchant_id', $product->merchant_id)
            ->where('email', $consumerData['email'])
            ->first();

        // If consumer doesn't exist, create a new one
        if (!$consumer) {
            $consumerData['merchant_id'] = $product->merchant_id;
            $consumer = $this->consumerService->create($consumerData);
        } else {
            // Update consumer with new data if provided (name, mobile_number)
            $updateData = [];
            if (isset($consumerData['name']) && !empty($consumerData['name'])) {
                $updateData['name'] = $consumerData['name'];
            }
            if (isset($consumerData['mobile_number']) && !empty($consumerData['mobile_number'])) {
                $updateData['mobile_number'] = $consumerData['mobile_number'];
            }
            if (!empty($updateData)) {
                $consumer->update($updateData);
                $consumer->refresh();
            }
        }

        // Create invoice with product
        $invoiceData = [
            'consumer_id' => $consumer->id,
            'merchant_id' => $product->merchant_id,
            'total_fee' => $product->fee,
            'invoice_details' => [
                [
                    'product_id' => $product->id,
                    'fee' => $product->fee,
                    'title' => $product->name,
                ]
            ],
        ];

        $invoice = $this->invoiceService->create($invoiceData);

        // Redirect to invoice page
        return redirect()->route('public.invoice.show', $invoice->uuid)
            ->with('success', 'Invoice created successfully. Please proceed with payment.');
    }
}

