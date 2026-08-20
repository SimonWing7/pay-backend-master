<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantReferral extends Model
{
    protected $fillable = [
        'merchant_uuid', 'edfundo_user_id', 'edfundo_user_email',
        'registered_at', 'registered_payload',
        'subscription_plan', 'subscribed_at', 'subscribed_payload',
        'nymcard_transaction_ref', 'credit_amount', 'credit_currency', 'credited_at', 'credit_payload',
        'commission_status', 'commission_amount', 'commission_settled_at', 'commission_settled_by',
    ];

    protected $casts = [
        'registered_at'         => 'datetime',
        'subscribed_at'         => 'datetime',
        'credited_at'           => 'datetime',
        'commission_settled_at' => 'datetime',
        'credit_amount'         => 'decimal:2',
        'commission_amount'     => 'decimal:2',
        'registered_payload'    => 'array',
        'subscribed_payload'    => 'array',
        'credit_payload'        => 'array',
    ];

    public function merchant(): BelongsTo
    {
        // "merchant_uuid" is a misnomer left over from an earlier design —
        // it's actually populated with the merchant's numeric id cast to
        // string (see Merchant\ReferralController::merchantUuid()), and
        // merchants have no separate uuid column. Joining against a real
        // "uuid" column here would throw ("Unknown column") the first time
        // this relation is eager-loaded against a non-empty result set.
        return $this->belongsTo(Merchant::class, 'merchant_uuid', 'id');
    }
}
