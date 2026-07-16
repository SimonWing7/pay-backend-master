<?php

namespace App\Http\Middleware;

use App\Models\MerchantApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * Reads the Authorization: Bearer <key> header, hashes it with SHA-256,
     * and looks it up in merchant_api_keys. If found, the merchant is attached
     * to the request so controllers can access it via $request->merchantFromKey.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Missing or invalid Authorization header. Expected: Authorization: Bearer <api-key>',
            ], 401);
        }

        $plaintext = substr($authHeader, 7);

        if (empty($plaintext)) {
            return response()->json(['message' => 'API key is empty.'], 401);
        }

        $apiKey = MerchantApiKey::findByPlaintext($plaintext);

        if (!$apiKey) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        // Touch last_used_at (non-blocking — ignore failures)
        try {
            $apiKey->update(['last_used_at' => now()]);
        } catch (\Throwable) {
        }

        // Attach both the API key record and the merchant to the request
        $request->attributes->set('apiKey', $apiKey);
        $request->attributes->set('merchantFromKey', $apiKey->merchant);

        return $next($request);
    }
}
