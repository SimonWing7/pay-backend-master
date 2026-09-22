<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a newly-invited admin through 2FA setup before they can reach
 * anything else — mirrors RequirePasswordChange for merchants. Scoped to
 * invited admins only (invite_accepted_at set): an admin created outside
 * the invite flow (e.g. seeded directly) is never blocked by this, so
 * existing admins who haven't opted into 2FA aren't retroactively forced
 * into it — only the onboarding moment for a brand new invite is gated.
 */
class RequireTwoFactorForInvitedAdmins
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if ($admin && $admin->invite_accepted_at && !$admin->hasTwoFactorEnabled()) {
            $allowed = [
                'admin.two-factor.index',
                'admin.two-factor.setup',
                'admin.two-factor.confirm',
                'admin.logout',
            ];

            if (!in_array($request->route()?->getName(), $allowed, true)) {
                return redirect()->route('admin.two-factor.setup');
            }
        }

        return $next($request);
    }
}
