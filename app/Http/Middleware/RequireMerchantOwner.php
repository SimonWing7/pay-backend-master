<?php

namespace App\Http\Middleware;

use App\Models\MerchantUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Billing, API keys, business settings, referrals and the team: owners only. */
class RequireMerchantOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->attributes->get('merchant_role', MerchantUser::ROLE_OWNER) !== MerchantUser::ROLE_OWNER) {
            return ResolveMerchantMember::deny($request, 'Only an account owner can do that.');
        }

        return $next($request);
    }
}
