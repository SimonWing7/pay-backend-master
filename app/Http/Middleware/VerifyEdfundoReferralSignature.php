<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEdfundoReferralSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.edfundo_referral.secret');

        if (!$secret) {
            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $signature = $request->header('X-Edfundo-Pay-Signature');

        if (!$signature) {
            return response()->json(['error' => 'Missing signature'], 401);
        }

        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
