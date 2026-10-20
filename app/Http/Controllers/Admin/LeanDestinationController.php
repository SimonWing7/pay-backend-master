<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\LeanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LeanDestinationController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.lean-destinations.create', ['prefill' => $this->prefillFromOnboarding($request->integer('merchant'))]);
    }

    /**
     * Fill the form from what a merchant entered in onboarding, so Edfundo
     * doesn't re-key bank details. Nothing is sent to Lean until they submit.
     */
    private function prefillFromOnboarding(int $merchantId): array
    {
        $merchant = $merchantId ? Merchant::find($merchantId) : null;
        $onboarding = $merchant?->onboarding;

        if (!$onboarding) {
            return [];
        }

        $bank = $onboarding->step('bank');
        $registration = $onboarding->step('registration');
        $business = $onboarding->step('business');

        return array_filter([
            'display_name' => $business['trading_name'] ?? null,
            // Lean only accepts letters, numbers and spaces in the legal name.
            'name' => isset($bank['account_holder_name']) ? trim(preg_replace('/[^A-Za-z0-9 ]/', '', $bank['account_holder_name'])) : null,
            'address' => $registration['address'] ?? null,
            'city' => $registration['city'] ?? null,
            'account_number' => $bank['account_number'] ?? null,
            'swift_code' => $bank['swift_code'] ?? null,
            'iban' => $bank['iban'] ?? null,
            'trade_license_number' => $registration['trade_license_number'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function store(Request $request, LeanService $lean): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'display_name'          => 'required|string|max:255',
            'name'                  => 'required|string|max:255',
            'address'               => 'required|string|max:255',
            'city'                  => 'required|string|max:255',
            'account_number'        => 'required|string|max:100',
            'swift_code'            => 'required|string|max:20',
            'iban'                  => 'required|string|max:34',
            'bank_type'             => 'nullable|string|max:20',
            'trade_license_number'  => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.lean-destinations.create')
                ->withErrors($validator)
                ->withInput();
        }

        $result = $lean->createDestination($validator->validated());

        if (!$result['success']) {
            return redirect()->route('admin.lean-destinations.create')
                ->withInput()
                ->with('error', 'Lean rejected the request: ' . ($result['error'] ?? 'unknown error'));
        }

        return redirect()->route('admin.lean-destinations.create')
            ->with('success', 'Destination created — copy the ID below into the merchant or entity record.')
            ->with('destination_id', $result['destination_id']);
    }
}
