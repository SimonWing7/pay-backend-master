<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingChargeAttempt extends Model
{
    public const OUTCOME_PENDING = 'pending';
    public const OUTCOME_AUTHORISED = 'authorised';
    public const OUTCOME_DECLINED = 'declined';
    public const OUTCOME_ERROR = 'error';
    public const OUTCOME_UNKNOWN = 'unknown';

    protected $fillable = [
        'billing_statement_id',
        'attempt_number',
        'cartid',
        'amount',
        'outcome',
        'telr_tran_ref',
        'telr_code',
        'message',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function statement()
    {
        return $this->belongsTo(BillingStatement::class, 'billing_statement_id');
    }
}
