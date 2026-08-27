<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A legal entity/company under one merchant login — e.g. a sports academy
 * operating under separate trade licenses in Dubai and Abu Dhabi, sharing
 * one bank account but needing separate Lean destinations (a destination is
 * tied to a trade license). Purely additive: a merchant with none of these
 * behaves exactly as before, routing payments via Merchant::lean_destination_id.
 */
class MerchantEntity extends Model
{
    protected $fillable = [
        'merchant_id',
        'name',
        'lean_destination_id',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
}
