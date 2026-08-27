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

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'mobile_number' => 'required|string',
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

        // Redirect to invoice page. Custom field values are carried through
        // as query params — the invoice page already falls back to
        // request('custom_fields.*') for pre-filling, so the payer doesn't
        // have to retype what they just entered here.
        return redirect()->route('public.invoice.show', array_merge(
            ['uuid' => $invoice->uuid],
            $request->filled('custom_fields') ? ['custom_fields' => $request->input('custom_fields')] : []
        ))->with('success', 'Invoice created successfully. Please proceed with payment.');
    }
}

