<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use App\Services\GroupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $preselectedConsumerId = $request->get('consumer_id');
        return view('merchant.invoices.create', compact('consumers', 'products', 'groups', 'preselectedConsumerId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'consumer_id'                       => ['nullable', Rule::exists('consumers', 'id')->where('merchant_id', $request->user()->id)],
            'total_fee'                         => 'required|numeric|min:0.01',
            'invoice_details'                   => 'required|array|min:1',
            'invoice_details.*.product_id'      => ['nullable', Rule::exists('products', 'id')->where('merchant_id', $request->user()->id)],
            'invoice_details.*.fee'             => 'required|numeric|min:0.01',
            'invoice_details.*.title'           => 'required|string|max:255',
            'link_type'                         => 'nullable|string|in:open,personal',
            'reference'                         => 'nullable|string|max:100',
            'new_consumer_name'                 => 'nullable|string|max:255',
            'new_consumer_email'                => 'nullable|email|max:255',
            'new_consumer_mobile'               => 'nullable|string|max:50',
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

        // Normalise custom_fields: filter empty labels and cast required to bool
        if (!empty($data['custom_fields'])) {
            $data['custom_fields'] = collect($data['custom_fields'])
                ->filter(fn($f) => !empty($f['label']))
                ->map(fn($f) => [
                    'label'    => trim($f['label']),
                    'required' => (bool) ($f['required'] ?? false),
                ])
                ->values()
                ->toArray();

            if (empty($data['custom_fields'])) {
                $data['custom_fields'] = null;
            }
        } else {
            $data['custom_fields'] = null;
        }

        // If personal link with a new individual, create them on the fly (or reuse if email already exists)
        if (
            $request->input('link_type') === 'personal' &&
            !$request->input('consumer_id') &&
            $request->input('new_consumer_name')
        ) {
            $email  = $request->input('new_consumer_email') ?: null;
            $mobile = $request->input('new_consumer_mobile') ?: null;

            // Check if an individual with the same email or mobile already exists for this merchant
            $consumer = \App\Models\Consumer::where('merchant_id', $data['merchant_id'])
                ->where(function ($q) use ($email, $mobile) {
                    if ($email)  $q->orWhere('email', $email);
                    if ($mobile) $q->orWhere('mobile_number', $mobile);
                })
                ->first();

            // Create them only if no match found
            if (!$consumer) {
                $consumer = \App\Models\Consumer::create([
                    'merchant_id'   => $data['merchant_id'],
                    'name'          => $request->input('new_consumer_name'),
                    'email'         => $email,
                    'mobile_number' => $mobile,
                ]);
            }

            $data['consumer_id'] = $consumer->id;
        }

        $this->invoiceService->create($data);

        return redirect()->route('merchant.invoices.index')
            ->with('success', 'Payment link created successfully');
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
            'product_id' => ['required', Rule::exists('products', 'id')->where('merchant_id', $request->user()->id)],
            'consumer_ids' => 'required|array|min:1',
            'consumer_ids.*' => ['required', Rule::exists('consumers', 'id')->where('merchant_id', $request->user()->id)],
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

        // Status can only be changed by the payment callback, never by the merchant manually
        if ($invoice->status->value === 10) {
            // Paid invoices are fully locked
            return redirect()->route('merchant.invoices.show', $invoice->id)
                ->with('info', 'Paid payment links cannot be edited.');
        }

        $validator = Validator::make($request->all(), [
            'total_fee'                => 'sometimes|numeric|min:0.01',
            'reference'                => 'nullable|string|max:100',
            'custom_fields'            => 'nullable|array|max:5',
            'custom_fields.*.label'    => 'required_with:custom_fields.*|string|max:100',
            'custom_fields.*.required' => 'nullable',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Never allow status to be changed via this endpoint
        $updateData = collect($validator->validated())->except(['status'])->toArray();

        // Normalise custom_fields: filter empty labels and cast required to bool
        if (!empty($updateData['custom_fields'])) {
            $updateData['custom_fields'] = collect($updateData['custom_fields'])
                ->filter(fn($f) => !empty($f['label']))
                ->map(fn($f) => [
                    'label'    => trim($f['label']),
                    'required' => (bool) ($f['required'] ?? false),
                ])
                ->values()
                ->toArray();

            if (empty($updateData['custom_fields'])) {
                $updateData['custom_fields'] = null;
            }
        } else {
            $updateData['custom_fields'] = null;
        }

        $this->invoiceService->update($invoice, $updateData);

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

    public function archive(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        $this->invoiceService->update($invoice, ['status' => \App\Enums\InvoiceStatus::Archived->value]);

        return redirect()->route('merchant.invoices.index')
            ->with('success', 'Payment link archived.');
    }

    public function unarchive(Request $request, int $id): RedirectResponse
    {
        $merchantId = $request->user()->id;
        $invoice = $this->invoiceService->getById($id, $merchantId);

        if (!$invoice) {
            abort(404, 'Invoice not found');
        }

        $this->invoiceService->update($invoice, ['status' => \App\Enums\InvoiceStatus::Draft->value]);

        return redirect()->route('merchant.invoices.index', ['status' => 30])
            ->with('success', 'Payment link restored to draft.');
    }
}

