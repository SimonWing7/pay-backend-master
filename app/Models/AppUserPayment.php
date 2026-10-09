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
        'review_notified_at',
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
            'review_notified_at' => 'datetime',
        ];
    }

    /**
     * Lean's PENDING_WITH_BANK is a final status from Lean's side — the bank
     * accepted the instruction without confirming it, and Lean never sends
     * a follow-up, so these need a human to reconcile against the
     * merchant's bank account once they've sat long enough.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('status', PaymentStatus::Initiated->value)
            ->where('lean_metadata->latest_iso_status', 'PENDING_WITH_BANK')
            ->where('created_at', '<', now()->subHours((int) config('invoices.pending_review_hours', 6)));
    }

    public function isNeedsReview(): bool
    {
        return $this->status === PaymentStatus::Initiated
            && ($this->lean_metadata['latest_iso_status'] ?? null) === 'PENDING_WITH_BANK'
            && $this->created_at->lt(now()->subHours((int) config('invoices.pending_review_hours', 6)));
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
