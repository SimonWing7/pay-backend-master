<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\MerchantReferral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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

    /**
     * Manual safety net for referrals automated matching can't catch —
     * a parent paid via Edfundo Pay but signed up to the main app under a
     * different email/mobile (e.g. the other parent), or signed up
     * directly without ever clicking the referral link at all. Neither
     * case leaves a digital trail the CSV import or webhook path can
     * follow, so this is deliberately a manual, admin-entered fallback.
     */
    public function create(): View
    {
        $merchants = Merchant::orderBy('name')->get(['id', 'name']);

        return view('admin.referrals.create', compact('merchants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'merchant_id'         => 'required|exists:merchants,id',
            'edfundo_user_email'  => 'required|email',
            'edfundo_user_id'     => 'required|string|max:255',
            'registered_at'       => 'nullable|date',
            'is_subscribed'       => 'nullable|boolean',
            'subscribed_at'       => 'nullable|date',
            'notes'               => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $merchantUuid = (string) $data['merchant_id'];

        $exists = MerchantReferral::where('merchant_uuid', $merchantUuid)
            ->where('edfundo_user_id', $data['edfundo_user_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withErrors(['edfundo_user_id' => 'A referral already exists for this merchant and user id.'])
                ->withInput();
        }

        $referral = new MerchantReferral([
            'merchant_uuid'      => $merchantUuid,
            'edfundo_user_id'    => $data['edfundo_user_id'],
            'edfundo_user_email' => $data['edfundo_user_email'],
            'registered_at'      => $data['registered_at'] ?? now(),
            'registered_payload' => [
                'source'     => 'manual_entry',
                'created_by' => auth('admin')->user()->name ?? 'Admin',
                'notes'      => $data['notes'] ?? null,
            ],
        ]);

        if (!empty($data['is_subscribed'])) {
            $referral->subscription_plan = 'active';
            $referral->subscribed_at = $data['subscribed_at'] ?? now();
            $referral->commission_status = 'earned';
        }

        $referral->save();

        return redirect()->route('admin.referrals.index')->with('success', 'Referral created manually.');
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
