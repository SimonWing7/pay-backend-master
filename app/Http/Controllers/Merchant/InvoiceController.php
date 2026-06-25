<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceService $invoiceService,
        protected GroupService $groupService
    ) {
    }

    public function index(Request $request): View
    {
        $merchantId = $request->user()->id;
        $filters = [
            'status' => $request->get('status'),
            'consumer_id' => $request->get('consumer_id'),
            'group_id' => $request->get('group_id'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'search' => $request->get('search'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $invoices = $this->invoiceService->getAllByMerchant($merchantId, $filters, $sortBy, $sortDir, $perPage);
        $consumers = \App\Models\Consumer::where('merchant_id', $merchantId)->get();
        $groups = $this->groupService->getAllByMerchant($merchantId);
        return view('merchant.invoices.index', compact('invoices', 'consumers', 'groups'));
    }

    public function create(Request $request): View
    {
        $merchantId = $request->user()->id;
        $consumers = \App\Models\Consumer::where('merchant_id', $merchantId)->get();
        $products = \App\Models\Product::where('merchant_id', $merchantId)->get();
        $groups = \App\Models\Group::where('merchant_id', $merchantId)->with('consumers')->get();
        return view('merchant.invoices.create', compact('consumers', 'products', 'groups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'consumer_id' => 'required_without:group_id|exists:consumers,id',
            'group_id' => 'required_without:consumer_id|exists:groups,id',
            'total_fee' => 'required|numeric|min:0',
            'invoice_details' => 'required|array|min:1',
            'invoice_details.*.product_id' => 'required|exists:products,id',
            'invoice_details.*.fee' => 'required|numeric|min:0',
            'invoice_details.*.title' => 'sometimes|string',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['merchant_id'] = $request->user()->id;

        // If group_id is provided, create invoices for all consumers in the group (one invoice per consumer)
        if (isset($data['group_id'])) {
            // Remove consumer_id if it exists when creating for group
            unset($data['consumer_id']);
            
            $invoices = $this->invoiceService->createForGroup($data);
            $count = $invoices->count();
            
            return redirect()->route('merchant.invoices.index')
                ->with('success', "Successfully created {$count} invoice(s) for all consumers in the group");
        }

        $this->invoiceService->create($data);

        return redirect()->route('merchant.invoices.index')
            ->with('success', 'Invoice created successfully');
    }

    public function createBulk(Request $request): View
    {
        $merchantId = $request->user()->id;
        $consumers = \App\Models\Consumer::where('merchant_id', $merchantId)->get();
        $products = \App\Models\Product::where('merchant_id', $merchantId)->get();
        return view('merchant.invoices.create-bulk', compact('consumers', 'products'));
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'consumer_ids' => 'required|array|min:1',
            'consumer_ids.*' => 'required|exists:consumers,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['merchant_id'] = $request->user()->id;

        try {
            $this->invoiceService->createBulk($data);
            return redirect()->route('merchant.invoices.index')
                ->with('success', 'Invoices created successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        return view('merchant.invoices.show', compact('invoice'));
    }

    public function edit(Request $request, int $id): View
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        return view('merchant.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        $validator = Validator::make($request->all(), [
            'total_fee' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $this->invoiceService->update($invoice, $validator->validated());

        return redirect()->route('merchant.invoices.index')
            ->with('success', 'Invoice updated successfully');
    }

    public function delete(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        $this->invoiceService->delete($invoice);

        return redirect()->route('merchant.invoices.index')
            ->with('success', 'Invoice deleted successfully');
    }
}

