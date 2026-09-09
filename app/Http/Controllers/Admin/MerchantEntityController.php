<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantEntity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MerchantEntityController extends Controller
{
    public function store(Request $request, int $merchantId): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:255',
            'lean_destination_id'    => 'nullable|string|max:255',
            'iban'                   => 'nullable|string|max:34',
            'fallback_bank_name'     => 'nullable|string|max:255',
            'fallback_account_name'  => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.merchants.edit', $merchantId)
                ->withErrors($validator, 'entity')
                ->withInput();
        }

        $data = $validator->validated();
        $data['merchant_id'] = $merchantId;

        MerchantEntity::create($data);

        return redirect()->route('admin.merchants.edit', $merchantId)
            ->with('success', 'Entity added successfully');
    }

    public function update(Request $request, int $merchantId, int $entityId): RedirectResponse
    {
        $entity = MerchantEntity::where('merchant_id', $merchantId)
            ->where('id', $entityId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name'                   => 'required|string|max:255',
            'lean_destination_id'    => 'nullable|string|max:255',
            'iban'                   => 'nullable|string|max:34',
            'fallback_bank_name'     => 'nullable|string|max:255',
            'fallback_account_name'  => 'nullable|string|max:255',
            'fallback_reference_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.merchants.edit', $merchantId)
                ->withErrors($validator, 'entity_' . $entityId)
                ->withInput();
        }

        $entity->update($validator->validated());

        return redirect()->route('admin.merchants.edit', $merchantId)
            ->with('success', 'Entity updated successfully');
    }

    public function destroy(int $merchantId, int $entityId): RedirectResponse
    {
        MerchantEntity::where('merchant_id', $merchantId)
            ->where('id', $entityId)
            ->delete();

        return redirect()->route('admin.merchants.edit', $merchantId)
            ->with('success', 'Entity removed successfully');
    }
}
