<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    public function index(Request $request): View
    {
        $merchantId = $request->user()->id;
        $filters = [
            'search' => $request->get('search'),
            'min_fee' => $request->get('min_fee'),
            'max_fee' => $request->get('max_fee'),
            'state' => $request->get('status'), // filter-sort component uses 'status' as the field name
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $products = $this->productService->getAllByMerchant($merchantId, $filters, $sortBy, $sortDir, $perPage);
        return view('merchant.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('merchant.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'fee' => 'required|numeric|min:0',
            'custom_fields'                     => 'nullable|array|max:5',
            'custom_fields.*.label'             => 'required_with:custom_fields.*|string|max:100',
            'custom_fields.*.required'          => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['merchant_id'] = $request->user()->id;
        $data['state'] = $data['state'] ?? 'active'; // Default to 'active' if not provided
        $data['custom_fields'] = $this->normaliseCustomFields($data['custom_fields'] ?? null);

        $this->productService->create($data);

        return redirect()->route('merchant.products.index')
            ->with('success', 'Product created successfully');
    }

    public function show(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $payments = $this->productService->getPaymentsForProduct($product, $merchantId);
        $totalPayments = $payments->count();
        $totalAmount = $payments->where('status', \App\Enums\PaymentStatus::Complete)->sum(function ($payment) {
            return $payment->invoice->total_fee ?? 0;
        });

        return view('merchant.products.show', compact('product', 'payments', 'totalPayments', 'totalAmount'));
    }

    public function edit(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        return view('merchant.products.edit', compact('product'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'fee' => 'sometimes|numeric|min:0',
            'state' => 'sometimes|in:active,archived',
            'custom_fields'                     => 'nullable|array|max:5',
            'custom_fields.*.label'             => 'required_with:custom_fields.*|string|max:100',
            'custom_fields.*.required'          => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['custom_fields'] = $this->normaliseCustomFields($data['custom_fields'] ?? null);

        $this->productService->update($product, $data);

        return redirect()->route('merchant.products.index')
            ->with('success', 'Product updated successfully');
    }

    public function delete(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        // Check if product has any invoices
        if ($product->invoiceDetails()->exists()) {
            // Archive instead of delete if product has invoices
            $this->productService->update($product, ['state' => 'archived']);
            return redirect()->route('merchant.products.index')
                ->with('success', 'Product archived successfully because it has associated invoices');
        }

        $this->productService->delete($product);

        return redirect()->route('merchant.products.index')
            ->with('success', 'Product deleted successfully');
    }

    public function toggleState(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $this->productService->toggleState($product);
        $newState = $product->fresh()->state;

        return redirect()->back()
            ->with('success', "Product state changed to {$newState} successfully");
    }

    public function exportPayments(Request $request, int $id): StreamedResponse
    {
        $merchantId = $request->user()->id;
        $product = $this->productService->getById($id, $merchantId);

        if (!$product) {
            abort(404, 'Product not found');
        }

        $payments = $this->productService->getPaymentsForProduct($product, $merchantId);

        $filename = 'product_' . $product->id . '_payments_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payments, $product) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, ['Payment ID', 'Consumer Name', 'Consumer ID', 'Invoice UUID', 'Status', 'Product Fee', 'Created At']);

            // Data rows
            foreach ($payments as $payment) {
                // Get the product fee from invoice details
                $productFee = 0;
                if ($payment->invoice && $payment->invoice->invoiceDetails) {
                    $productDetail = $payment->invoice->invoiceDetails->firstWhere('product_id', $product->id);
                    $productFee = $productDetail->fee ?? 0;
                }

                fputcsv($file, [
                    $payment->id,
                    $payment->invoice->consumer->name ?? 'N/A',
                    $payment->invoice->consumer->id ?? 'N/A',
                    $payment->invoice->uuid ?? 'N/A',
                    $payment->status->label(),
                    $productFee,
                    $payment->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Filter out empty labels and cast 'required' to a real bool, same
     * normalisation Invoice custom fields already use.
     */
    private function normaliseCustomFields(?array $customFields): ?array
    {
        if (empty($customFields)) {
            return null;
        }

        $normalised = collect($customFields)
            ->filter(fn ($f) => !empty($f['label']))
            ->map(fn ($f) => [
                'label'    => trim($f['label']),
                'required' => (bool) ($f['required'] ?? false),
            ])
            ->values()
            ->toArray();

        return !empty($normalised) ? $normalised : null;
    }
}

