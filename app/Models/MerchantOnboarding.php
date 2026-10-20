<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantOnboarding extends Model
{
    protected $fillable = [
        'merchant_id', 'current_step', 'data', 'signatory_id_path', 'bank_letter_path',
        'terms_accepted_at', 'terms_accepted_ip', 'terms_accepted_by',
        'submitted_at', 'reviewed_at', 'reviewed_by', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'terms_accepted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class)->withTrashed();
    }

    /** Saved answers for one step (empty array if none yet). */
    public function step(string $step): array
    {
        return $this->data[$step] ?? [];
    }
}
