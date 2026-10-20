<?php

namespace App\Http\Controllers;

use App\Models\LeanBank;
use App\Models\Product;
use App\Services\ConsumerService;
use App\Services\InvoiceService;
use App\Services\LeanService;
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
            ->with(['merchant', 'merchantEntity'])
            ->firstOrFail();

        if (!$product->merchant || !$product->merchant->acceptsPayments()) {
            abort(403, 'This product is not available. This merchant is not able to take payments right now.');
        }

        $liveBanks = LeanBank::where('is_available', true)->orderBy('name')->get(['name']);

        return view('public.product', compact('product', 'liveBanks'));
    }

    public function store(Request $request, string $uuid): RedirectResponse|View
    {
        $product = Product::where('uuid', $uuid)
            ->where('state', 'active')
            ->with('merchant')
            ->firstOrFail();

        if (!$product->merchant || !$product->merchant->acceptsPayments()) {
            abort(403, 'This product is not available. This merchant is not able to take payments right now.');
        }

        $rules = [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_mobile' => 'required|string|max:50',
            'custom_fields' => 'nullable|array',
        ];

        if ($product->custom_fields) {
            foreach ($product->custom_fields as $field) {
                $key = 'custom_fields.' . $field['label'];
                $rules[$key] = !empty($field['required']) ? 'required|string|max:500' : 'nullable|string|max:500';
            }
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if consumer already exists with this email and merchant_id
        $validated = $validator->validated();
        $consumer = \App\Models\Consumer::where('merchant_id', $product->merchant_id)
            ->where('email', $validated['customer_email'])
            ->first();

        if (!$consumer) {
            $consumer = $this->consumerService->create([
                'merchant_id'   => $product->merchant_id,
                'name'          => $validated['customer_name'],
                'email'         => $validated['customer_email'],
                'mobile_number' => $validated['customer_mobile'],
            ]);
        } else {
            // Keep the contact details current with what they just entered
            $consumer->update([
                'name'          => $validated['customer_name'],
                'mobile_number' => $validated['customer_mobile'],
            ]);
        }

        // Create invoice with product — custom_fields schema carries over from
        // the product so the invoice page still knows what to collect/display,
        // even though the actual value was already captured here.
        $invoiceData = [
            'consumer_id' => $consumer->id,
            'merchant_id' => $product->merchant_id,
            'merchant_entity_id' => $product->merchant_entity_id,
            'total_fee' => $product->fee,
            'custom_fields' => $product->custom_fields,
            'invoice_details' => [
                [
                    'product_id' => $product->id,
                    'fee' => $product->fee,
                    'title' => $product->name,
                ]
            ],
        ];

        $invoice = $this->invoiceService->create($invoiceData);

        // Straight into the bank flow, exactly as submitting a payment link
        // page does — the customer already entered their details and
        // clicked the AlTareq button here, so there's no second page to
        // click through. If starting the payment fails, pay() sends them to
        // the invoice page with an error and the same form to retry.
        return app(PublicInvoiceController::class)->pay($invoice->uuid, app(LeanService::class), $request);
    }
}

