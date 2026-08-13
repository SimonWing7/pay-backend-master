<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantReferral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $query = MerchantReferral::with('merchant')->orderBy('registered_at', 'desc');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('merchant_uuid', 'like', "%{$search}%")
                  ->orWhere('edfundo_user_email', 'like', "%{$search}%")
                  ->orWhere('edfundo_user_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('commission_status', $status);
        }

        $referrals = $query->paginate(50)->withQueryString();

        $totalReferrals         = MerchantReferral::count();
        $pendingCount           = MerchantReferral::where('commission_status', 'pending')->count();
        $earnedCount            = MerchantReferral::where('commission_status', 'earned')->count();
        $settledCount           = MerchantReferral::where('commission_status', 'settled')->count();
        $totalCommissionEarned  = MerchantReferral::whereIn('commission_status', ['earned', 'settled'])->sum('commission_amount');
        $totalCommissionSettled = MerchantReferral::where('commission_status', 'settled')->sum('commission_amount');

        return view('admin.referrals.index', compact(
            'referrals',
            'totalReferrals', 'pendingCount', 'earnedCount', 'settledCount',
            'totalCommissionEarned', 'totalCommissionSettled'
        ));
    }

    public function settle(Request $request, int $id): RedirectResponse
    {
        $referral = MerchantReferral::findOrFail($id);

        if ($referral->commission_status !== 'earned') {
            return redirect()->back()->with('error', 'Only referrals with status "earned" can be settled.');
        }

        $referral->update([
            'commission_status'     => 'settled',
            'commission_settled_at' => now(),
            'commission_settled_by' => auth('admin')->user()->name ?? 'Admin',
        ]);

        return redirect()->back()->with('success', 'Commission marked as settled.');
    }

    public function export(Request $request)
    {
        $referrals = MerchantReferral::with('merchant')->orderBy('registered_at', 'desc')->get();

        $filename = 'referrals_' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($referrals) {
            $h = fopen('php://output', 'w');
            fputcsv($h, [
                'ID', 'Merchant UUID', 'Merchant Name',
                'User ID', 'User Email',
                'Registered At', 'Subscription Plan', 'Subscribed At',
                'Nymcard Ref', 'Credit Amount', 'Credit Currency', 'Credited At',
                'Commission Status', 'Commission Amount (AED)',
                'Settled At', 'Settled By',
            ]);
            foreach ($referrals as $r) {
                fputcsv($h, [
                    $r->id,
                    $r->merchant_uuid,
                    optional($r->merchant)->name ?? '—',
                    $r->edfundo_user_id,
                    $r->edfundo_user_email,
                    $r->registered_at?->format('Y-m-d H:i:s'),
                    $r->subscription_plan,
                    $r->subscribed_at?->format('Y-m-d H:i:s'),
                    $r->nymcard_transaction_ref,
                    $r->credit_amount,
                    $r->credit_currency,
                    $r->credited_at?->format('Y-m-d H:i:s'),
                    $r->commission_status,
                    $r->commission_amount,
                    $r->commission_settled_at?->format('Y-m-d H:i:s'),
                    $r->commission_settled_by,
                ]);
            }
            fclose($h);
        }, 200, $headers);
    }
}
