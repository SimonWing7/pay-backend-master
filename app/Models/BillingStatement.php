<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingStatement extends Model
{
    public const STATUS_ISSUED = 'issued';
    // Claimed by a charge run and waiting on Telr. Committed BEFORE the call so
    // a crash mid-call can never leave a statement that looks safe to re-charge.
    public const STATUS_CHARGING = 'charging';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_AWAITING_CARD = 'awaiting_card';
    public const STATUS_NEEDS_REVIEW = 'needs_review';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'merchant_id',
        'number',
        'period_start',
        'period_end',
        'volume',
        'fee_percentage',
        'fee_amount',
        'currency',
        'status',
        'issued_at',
        'emailed_at',
        'charge_due_at',
        'next_attempt_at',
        'attempts',
        'first_failed_at',
        'paid_at',
        'telr_tran_ref',
        'paid_method',
        'failure_message',
        'awaiting_card_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'volume' => 'decimal:2',
            'fee_percentage' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'emailed_at' => 'datetime',
            'charge_due_at' => 'datetime',
            'next_attempt_at' => 'datetime',
            'first_failed_at' => 'datetime',
            'paid_at' => 'datetime',
            'awaiting_card_notified_at' => 'datetime',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function attemptsLog()
    {
        return $this->hasMany(BillingChargeAttempt::class)->orderBy('attempt_number');
    }

    /** "October 2026" */
    public function periodLabel(): string
    {
        return $this->period_start->format('F Y');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ISSUED => 'Scheduled',
            self::STATUS_CHARGING => 'Processing',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Payment failed',
            self::STATUS_AWAITING_CARD => 'Card needed',
            self::STATUS_NEEDS_REVIEW => 'Under review',
            self::STATUS_VOID => 'Void',
            default => ucfirst($this->status),
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_AWAITING_CARD], true);
    }
}
