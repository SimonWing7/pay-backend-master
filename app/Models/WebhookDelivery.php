<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One notification to a merchant's webhook URL, and what happened when we
 * tried to deliver it. Failed attempts are retried (see WebhookService).
 */
class WebhookDelivery extends Model
{
    public const PENDING = 'pending';
    public const DELIVERED = 'delivered';
    public const FAILED = 'failed';           // out of retries
    public const SUPERSEDED = 'superseded';   // no longer worth sending

    protected $fillable = [
        'merchant_id', 'event', 'reference', 'payload', 'status', 'attempts',
        'next_attempt_at', 'last_http_status', 'last_error', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'next_attempt_at' => 'datetime',
            'delivered_at'    => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
