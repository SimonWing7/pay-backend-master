<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppUserPayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'app_user_id',
        'invoice_id',
        'token',
        'status',
        'payment_channel',
        'customer_name',
        'customer_email',
        'customer_mobile',
        'custom_field_values',
        'nymcard_resource_id',
        'nymcard_token',
        'nymcard_user_id',
        'nymcard_metadata',
        'lean_payment_intent_id',
        'lean_metadata',
        'flow_success_data',
        'flow_failure_data',
        'flow_done_data',
        'flow_success_at',
        'flow_failure_at',
        'flow_done_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'custom_field_values' => 'array',
            'nymcard_metadata' => 'array',
            'lean_metadata' => 'array',
            'flow_success_data' => 'array',
            'flow_failure_data' => 'array',
            'flow_done_data' => 'array',
            'flow_success_at' => 'datetime',
            'flow_failure_at' => 'datetime',
            'flow_done_at' => 'datetime',
        ];
    }

    /**
     * Get the app user that owns the payment.
     * Returns null for web-based payments where no AppUser account exists.
     */
    public function appUser()
    {
        return $this->belongsTo(AppUser::class);
    }

    /**
     * Get the invoice for the payment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Determine if this is a web-based payment (no AppUser required).
     */
    public function isWebPayment(): bool
    {
        return is_null($this->app_user_id) || $this->payment_channel === 'web';
    }
}
