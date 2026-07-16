<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function index(Request $request): View
    {
        $merchant = $request->user();
        $keys     = MerchantApiKey::where('merchant_id', $merchant->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return view('merchant.settings.api-keys', compact('keys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $merchant = $request->user();

        // Limit to 5 active keys per merchant
        $activeCount = MerchantApiKey::where('merchant_id', $merchant->id)
            ->whereNull('deleted_at')
            ->count();

        if ($activeCount >= 5) {
            return redirect()->route('merchant.settings.api-keys')
                ->with('error', 'You can have a maximum of 5 active API keys. Please revoke one before generating a new one.');
        }

        ['key' => $key, 'plaintext' => $plaintext] = MerchantApiKey::generate(
            $merchant->id,
            $request->input('name')
        );

        // Flash the plaintext key — shown once, never stored
        return redirect()->route('merchant.settings.api-keys')
            ->with('new_key', $plaintext)
            ->with('new_key_name', $key->name);
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $merchant = $request->user();

        $key = MerchantApiKey::where('id', $id)
            ->where('merchant_id', $merchant->id)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $key->delete();

        return redirect()->route('merchant.settings.api-keys')
            ->with('success', "API key \"{$key->name}\" has been revoked.");
    }
}
