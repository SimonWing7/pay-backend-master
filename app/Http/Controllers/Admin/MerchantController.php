<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MerchantService;
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:merchants,email',
            'password' => 'required|string|min:8',
            'is_active' => 'sometimes|boolean',
            'iban' => 'nullable|string|max:34',
            'merchant_trading_name' => 'nullable|string|max:255',
            'category_code' => 'nullable|string|max:10',
            'sic_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $this->merchantService->create($validator->validated());

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant created successfully');
    }

    public function show(int $id): View
    {
        $merchant = $this->merchantService->getById($id);

        if (!$merchant) {
            abort(404, 'Merchant not found');
        }

        return view('admin.merchants.show', compact('merchant'));
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:merchants,email,' . $id,
            'password' => 'nullable|string|min:8',
            'is_active' => 'sometimes|boolean',
            'iban' => 'nullable|string|max:34',
            'merchant_trading_name' => 'nullable|string|max:255',
            'category_code' => 'nullable|string|max:10',
            'sic_code' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        $this->merchantService->update($merchant, $validator->validated());

        return redirect()->route('admin.merchants.index')
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

