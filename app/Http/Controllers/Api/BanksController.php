<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeanBank;
use Illuminate\Http\JsonResponse;

class BanksController extends Controller
{
    /**
     * Public, unauthenticated list of currently live banks — consumed by our
     * own hosted checkout page and by merchants' own checkout UIs (Magento,
     * custom platforms) so nobody has to hand-maintain a bank list.
     */
    public function index(): JsonResponse
    {
        $banks = LeanBank::where('is_available', true)
            ->orderBy('name')
            ->get(['identifier', 'name', 'logo_url']);

        return response()->json(['data' => $banks]);
    }
}
