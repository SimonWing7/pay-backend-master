<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $merchant = auth('merchants')->user();

        // Invited members chose their own password; the flag is about the
        // owner's login only and must not force them to change it.
        if ($merchant && $merchant->must_change_password && !$request->session()->has('merchant_member_id')) {
            // Allow access to password change routes and logout
            if (!$request->routeIs('merchant.password.change') && 
                !$request->routeIs('merchant.password.change.post') && 
                !$request->routeIs('merchant.logout')) {
                return redirect()->route('merchant.password.change');
            }
        }

        return $next($request);
    }
}

