<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyEdfundoReferralSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.edfundo_referral.secret');

        if (!$secret) {
            // Every referral call fails until this is fixed — worth an
            // immediate alert, not something found by chance in the logs.
            Log::error('Referral webhook: EDFUNDO_REFERRAL_SIGNING_SECRET not configured');
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $signature = $request->header('X-Edfundo-Pay-Signature');

        if (!$signature) {
            Log::error('Referral webhook: missing signature header', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            Log::error('Referral webhook: invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
