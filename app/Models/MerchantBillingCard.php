<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantBillingCard extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REPLACED = 'replaced';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'merchant_id',
        'telr_tran_ref',
        'card_type',
        'last4',
        'expiry_month',
        'expiry_year',
        'status',
        'setup_cartid',
        'telr_order_ref',
        'consent_at',
        'consent_ip',
        'verification_refunded_at',
    ];

    // The reference is what lets Edfundo charge this card, so it never
    // leaves the server — not in JSON, not in logs.
    protected $hidden = ['telr_tran_ref'];

    protected function casts(): array
    {
        return [
            'telr_tran_ref' => 'encrypted',
            'consent_at' => 'datetime',
            'verification_refunded_at' => 'datetime',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** "Visa Credit ending 1111, expires 12/2030" */
    public function label(): string
    {
        $expiry = $this->expiry_month && $this->expiry_year
            ? sprintf(', expires %02d/%d', $this->expiry_month, $this->expiry_year)
            : '';

        return trim(($this->card_type ?: 'Card') . ' ending ' . $this->last4) . $expiry;
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }

        return now()->startOfMonth()->gt(
            \Illuminate\Support\Carbon::create($this->expiry_year, $this->expiry_month, 1)->startOfMonth()
        );
    }
}
