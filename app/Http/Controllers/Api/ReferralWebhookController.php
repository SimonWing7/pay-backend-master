<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MerchantReferral;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ReferralWebhookController extends Controller
{
    /**
     * User registered via referral link.
     */
    public function registered(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_uuid'     => 'required|string',
            'edfundo_user_id'   => 'required|string',
            'edfundo_user_email' => 'nullable|email',
            'registered_at'     => 'nullable|date',
        ]);

        MerchantReferral::updateOrCreate(
            ['merchant_uuid' => $data['merchant_uuid'], 'edfundo_user_id' => $data['edfundo_user_id']],
            [
                'edfundo_user_email' => $data['edfundo_user_email'] ?? null,
                'registered_at'      => $data['registered_at'] ?? now(),
                'registered_payload' => $request->all(),
            ]
        );

        Log::info('Referral registered', ['merchant' => $data['merchant_uuid'], 'user' => $data['edfundo_user_id']]);

        return response()->json(['ok' => true]);
    }

    /**
     * User subscribed -- commission becomes earned.
     */
    public function subscribed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_uuid'     => 'required|string',
            'edfundo_user_id'   => 'required|string',
            'subscription_plan' => 'nullable|string',
            'subscribed_at'     => 'nullable|date',
        ]);

        $referral = MerchantReferral::firstOrCreate(
            ['merchant_uuid' => $data['merchant_uuid'], 'edfundo_user_id' => $data['edfundo_user_id']]
        );

        $referral->update([
            'subscription_plan'    => $data['subscription_plan'] ?? null,
            'subscribed_at'        => $data['subscribed_at'] ?? now(),
            'subscribed_payload'   => $request->all(),
            'commission_status'    => 'earned',
        ]);

        Log::info('Referral subscribed', ['merchant' => $data['merchant_uuid'], 'user' => $data['edfundo_user_id']]);

        return response()->json(['ok' => true]);
    }

    /**
     * Nymcard credit issued to the referred user.
     */
    public function creditIssued(Request $request): JsonResponse
    {
        $data = $request->validate([
            'merchant_uuid'            => 'required|string',
            'edfundo_user_id'          => 'required|string',
            'nymcard_transaction_ref'  => 'nullable|string',
            'credit_amount'            => 'nullable|numeric',
            'credit_currency'          => 'nullable|string',
            'credited_at'              => 'nullable|date',
        ]);

        $referral = MerchantReferral::where('merchant_uuid', $data['merchant_uuid'])
            ->where('edfundo_user_id', $data['edfundo_user_id'])
            ->first();

        if (!$referral) {
            return response()->json(['error' => 'Referral not found'], 404);
        }

        $referral->update([
            'nymcard_transaction_ref' => $data['nymcard_transaction_ref'] ?? null,
            'credit_amount'           => $data['credit_amount'] ?? null,
            'credit_currency'         => $data['credit_currency'] ?? null,
            'credited_at'             => $data['credited_at'] ?? now(),
            'credit_payload'          => $request->all(),
        ]);

        Log::info('Referral credit issued', ['merchant' => $data['merchant_uuid'], 'user' => $data['edfundo_user_id']]);

        return response()->json(['ok' => true]);
    }
}
