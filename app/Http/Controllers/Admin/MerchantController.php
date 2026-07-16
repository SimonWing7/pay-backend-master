<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantApiKey;
use App\Services\MerchantService;
use App\Services\WebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MerchantController extends Controller
{
    public function __construct(
        protected MerchantService $merchantService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'is_active' => $request->get('is_active'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $merchants = $this->merchantService->getAll($filters, $sortBy, $sortDir, $perPage);
        return view('admin.merchants.index', compact('merchants'));
    }

    public function create(): View
    {
        return view('admin.merchants.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                    => 'required|string|max:255',
            'email'                   => 'required|email|unique:merchants,email',
            'password'                => 'required|string|min:8',
            'is_active'               => 'sometimes|boolean',
            'iban'                    => 'nullable|string|max:34',
            'merchant_trading_name'   => 'nullable|string|max:255',
            'category_code'           => 'nullable|string|max:10',
            'sic_code'                => 'nullable|string|max:10',
            'support_email'           => 'nullable|email|max:255',
            'support_phone'           => 'nullable|string|max:50',
            'website'                 => 'nullable|url|max:2048',
            'webhook_url'             => 'nullable|url|max:2048',
            'fallback_type'           => 'nullable|in:,payment_gateway,bank_transfer',
            'fallback_payment_url'    => 'nullable|url|max:2048',
            'fallback_bank_name'      => 'nullable|string|max:255',
            'fallback_account_name'   => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $data = $validator->validated();

        // Normalise empty string to null for fallback_type
        if (isset($data['fallback_type']) && $data['fallback_type'] === '') {
            $data['fallback_type'] = null;
        }

        // Clear fields that don't apply to the chosen fallback type
        if (($data['fallback_type'] ?? null) !== 'payment_gateway') {
            $data['fallback_payment_url'] = null;
        }
        if (($data['fallback_type'] ?? null) !== 'bank_transfer') {
            $data['fallback_bank_name']      = null;
            $data['fallback_account_name']   = null;
            $data['fallback_reference_note'] = null;
        }

        // Auto-generate a webhook secret if a URL is provided at creation time
        if (!empty($data['webhook_url'])) {
            $data['webhook_secret'] = WebhookService::generateSecret();
        }

        $this->merchantService->create($data);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant created successfully');
    }

    public function show(int $id): View
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $apiKeys = MerchantApiKey::where('merchant_id', $id)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.merchants.show', compact('merchant', 'apiKeys'));
    }

    public function edit(int $id): View
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        return view('admin.merchants.edit', compact('merchant'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $validator = Validator::make($request->all(), [
            'name'                    => 'sometimes|string|max:255',
            'email'                   => 'sometimes|email|unique:merchants,email,' . $id,
            'password'                => 'nullable|string|min:8',
            'is_active'               => 'sometimes|boolean',
            'iban'                    => 'nullable|string|max:34',
            'merchant_trading_name'   => 'nullable|string|max:255',
            'category_code'           => 'nullable|string|max:10',
            'sic_code'                => 'nullable|string|max:10',
            'support_email'           => 'nullable|email|max:255',
            'support_phone'           => 'nullable|string|max:50',
            'website'                 => 'nullable|url|max:2048',
            'webhook_url'             => 'nullable|url|max:2048',
            'fallback_type'           => 'nullable|in:,payment_gateway,bank_transfer',
            'fallback_payment_url'    => 'nullable|url|max:2048',
            'fallback_bank_name'      => 'nullable|string|max:255',
            'fallback_account_name'   => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $data = $validator->validated();

        // Normalise empty string to null for fallback_type
        if (isset($data['fallback_type']) && $data['fallback_type'] === '') {
            $data['fallback_type'] = null;
        }

        // Clear gateway URL when not using payment_gateway
        if (($data['fallback_type'] ?? null) !== 'payment_gateway') {
            $data['fallback_payment_url'] = null;
        }

        // Clear transfer fields when not using bank_transfer
        if (($data['fallback_type'] ?? null) !== 'bank_transfer') {
            $data['fallback_bank_name']      = null;
            $data['fallback_account_name']   = null;
            $data['fallback_reference_note'] = null;
        }

        // Auto-generate webhook secret when a URL is being set and none exists yet
        if (!empty($data['webhook_url']) && empty($merchant->webhook_secret)) {
            $data['webhook_secret'] = WebhookService::generateSecret();
        }

        // Clear the secret when the webhook URL is removed
        if (isset($data['webhook_url']) && empty($data['webhook_url'])) {
            $data['webhook_secret'] = null;
        }

        $this->merchantService->update($merchant, $data);

        return redirect()->route('admin.merchants.show', $id)
            ->with('success', 'Merchant updated successfully');
    }

    public function delete(int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $this->merchantService->delete($merchant);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant deleted successfully');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        $merchant->is_active = !$merchant->is_active;
        $merchant->save();

        $status = $merchant->is_active ? 'activated' : 'deactivated';

        return redirect()->back()
            ->with('success', "Merchant {$status} successfully");
    }
}
