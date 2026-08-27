<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LeanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LeanDestinationController extends Controller
{
    public function create(): View
    {
        return view('admin.lean-destinations.create');
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
