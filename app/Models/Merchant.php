<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Merchant extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
        'iban',
        'bank_name',
        'account_holder_name',
        'lean_destination_id',
        'merchant_trading_name',
        'category_code',
        'sic_code',
        'support_email',
        'support_phone',
        'receipt_cc_email',
        'notification_email',
        'billing_email',
        'allow_payment_reconciliation',
        'show_reward_offer',
        'fee_percentage',
        'billing_enabled',
        'website',
        'webhook_url',
        'webhook_secret',
        'logo_path',
        'fallback_type',
        'fallback_payment_url',
        'fallback_bank_name',
        'fallback_account_name',
        'fallback_reference_note',
        'fallback_none_note',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'allow_payment_reconciliation' => 'boolean',
            'show_reward_offer' => 'boolean',
            'fee_percentage' => 'decimal:2',
            'billing_enabled' => 'boolean',
        ];
    }

    /**
     * Get the consumers for the merchant.
     */
    public function consumers()
    {
        return $this->hasMany(Consumer::class);
    }

    /**
     * Get the products for the merchant.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the invoices for the merchant.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Legal entities (companies/trade licenses) under this merchant login —
     * see MerchantEntity for why this exists.
     */
    public function entities()
    {
        return $this->hasMany(MerchantEntity::class);
    }

    public function billingCards()
    {
        return $this->hasMany(MerchantBillingCard::class);
    }

    /** The card Edfundo currently charges this merchant's monthly fees to. */
    public function activeBillingCard()
    {
        return $this->hasOne(MerchantBillingCard::class)
            ->where('status', MerchantBillingCard::STATUS_ACTIVE)
            ->latestOfMany();
    }

    /**
     * Where fee statements and charge results are emailed: the dedicated
     * billing email if one is set, otherwise the same address as the
     * notification email, then support email, then the login email.
     */
    public function billingRecipient(): string
    {
        return $this->billing_email ?: $this->notification_email ?: $this->support_email ?: $this->email;
    }

    public function billingStatements()
    {
        return $this->hasMany(BillingStatement::class);
    }

    /**
     * Get the groups for the merchant.
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\MerchantResetPasswordNotification($token, $this->email));
    }


    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }
}
