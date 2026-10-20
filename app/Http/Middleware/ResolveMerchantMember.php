<?php

namespace App\Http\Middleware;

use App\Models\MerchantUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Works out who is really behind the merchants-guard session and what role
 * they have, then enforces it.
 *
 * The primary merchant login has no member id in its session and is an owner.
 * An invited member is signed in as the parent Merchant (so all existing
 * merchant-scoped queries apply) with `merchant_member_id` in the session.
 * The member row is re-checked on every request, so removing someone or
 * deactivating the account takes effect immediately.
 *
 * Usage: `merchant.member` on the whole group; add `merchant.owner` on
 * owner-only routes (it relies on the role set here).
 */
class ResolveMerchantMember
{
    /** Routes a read-only viewer may still POST to. */
    private const VIEWER_WRITABLE = ['merchant.logout', 'merchant.settings.password.post'];

    public function handle(Request $request, Closure $next): Response
    {
        $merchant = auth('merchants')->user();
        $memberId = $request->session()->get('merchant_member_id');
        $member = null;

        if ($merchant && $memberId) {
            $member = MerchantUser::where('merchant_id', $merchant->id)->find($memberId);

            if (!$member || $member->isPending() || !$merchant->is_active) {
                auth('merchants')->logout();
                $request->session()->forget('merchant_member_id');

                return redirect()->route('merchant.login')
                    ->withErrors(['email' => 'Your access to this account has ended.']);
            }
        }

        $role = $member?->role ?? MerchantUser::ROLE_OWNER;

        $request->attributes->set('merchant_member', $member);
        $request->attributes->set('merchant_role', $role);
        View::share('teamMember', $member);
        View::share('teamRole', $role);

        if ($role === MerchantUser::ROLE_VIEWER
            && !$request->isMethodSafe()
            && !$request->routeIs(...self::VIEWER_WRITABLE)) {
            return self::deny($request, 'You have view-only access, so you can look but not change anything.');
        }

        return $next($request);
    }

    public static function deny(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            abort(403, $message);
        }

        // Unsafe methods go back to where they came from; a page the role
        // can't open at all goes to the dashboard.
        $back = $request->isMethodSafe() ? route('merchant.dashboard') : url()->previous(route('merchant.dashboard'));

        return redirect($back)->with('error', $message);
    }
}
