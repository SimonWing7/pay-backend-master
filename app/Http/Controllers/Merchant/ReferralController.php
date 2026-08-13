<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantReferral;
use Illuminate\View\View;

class ReferralController extends Controller
{
    private function merchantUuid(): string
    {
        return (string) auth('merchants')->user()->id;
    }

    public function index(): View
    {
        $merchantUuid = $this->merchantUuid();

        $referrals = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->orderBy('registered_at', 'desc')
            ->paginate(25);

        $totalReferrals = MerchantReferral::where('merchant_uuid', $merchantUuid)->count();

        $earnedCount = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->whereIn('commission_status', ['earned', 'settled'])->count();

        $totalEarned = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->whereIn('commission_status', ['earned', 'settled'])
            ->sum('commission_amount');

        $totalSettled = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->where('commission_status', 'settled')
            ->sum('commission_amount');

        $pendingPayout = $totalEarned - $totalSettled;

        $referralLink = "https://edfundo.com/edfundo-pay-rewards/?ref={$merchantUuid}"
            . "&utm_source=edfundo_pay&utm_medium=merchant_dashboard";

        return view('merchant.referrals.index', compact(
            'referrals',
            'totalReferrals', 'earnedCount',
            'totalEarned', 'totalSettled', 'pendingPayout',
            'referralLink'
        ));
    }

    public function export()
    {
        $merchantUuid = $this->merchantUuid();

        $referrals = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->orderBy('registered_at', 'desc')
            ->get();

        $filename = 'my_referrals_' . now()->format('Y-m-d') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($referrals) {
            $h = fopen('php://output', 'w');
            fputcsv($h, [
                'User Email', 'Registered At',
                'Subscription Plan', 'Subscribed At',
                'Reward Credit (AED)', 'Credit Currency', 'Credited At',
                'Commission Status', 'Commission Amount (AED)', 'Settled At',
            ]);
            foreach ($referrals as $r) {
                fputcsv($h, [
                    $r->edfundo_user_email ?? '—',
                    $r->registered_at?->format('Y-m-d H:i:s'),
                    $r->subscription_plan ?? '—',
                    $r->subscribed_at?->format('Y-m-d H:i:s'),
                    $r->credit_amount,
                    $r->credit_currency,
                    $r->credited_at?->format('Y-m-d H:i:s'),
                    $r->commission_status,
                    number_format($r->commission_amount, 2),
                    $r->commission_settled_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($h);
        }, 200, $headers);
    }
}
