<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A person invited into a merchant's account. Signing in as one of these still
 * authenticates the merchants guard as the parent Merchant (so every existing
 * merchant-scoped query keeps working); the role below decides what they may do.
 */
class MerchantUser extends Model
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_STAFF = 'staff';
    public const ROLE_VIEWER = 'viewer';

    public const ROLES = [
        self::ROLE_OWNER => [
            'label' => 'Owner',
            'description' => 'Everything, including billing, API keys, business settings and managing the team.',
        ],
        self::ROLE_STAFF => [
            'label' => 'Staff',
            'description' => 'Day-to-day work: products, payers, payment links, payments and reconciliation.',
        ],
        self::ROLE_VIEWER => [
            'label' => 'Viewer',
            'description' => 'Read-only. Can see everything day-to-day but cannot send, create or change anything.',
        ],
    ];

    public const INVITE_VALID_HOURS = 168;  // 7 days
    public const RESET_VALID_HOURS = 2;

    protected $fillable = [
        'merchant_id', 'name', 'email', 'password', 'role',
        'setup_token_hash', 'setup_token_expires_at', 'invited_at', 'invited_by', 'last_login_at',
    ];

    protected $hidden = ['password', 'setup_token_hash'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'setup_token_expires_at' => 'datetime',
            'invited_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class)->withTrashed();
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role]['label'] ?? ucfirst($this->role);
    }

    /** True until they have chosen a password. */
    public function isPending(): bool
    {
        return $this->password === null;
    }

    /**
     * Create a fresh one-time set-password token, replacing any earlier one.
     * Returns the plain token (only ever emailed, never stored).
     */
    public function issueSetupToken(int $validHours): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'setup_token_hash' => hash('sha256', $token),
            'setup_token_expires_at' => now()->addHours($validHours),
        ])->save();

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        $member = self::where('setup_token_hash', hash('sha256', $token))->first();

        if (!$member || !$member->setup_token_expires_at || $member->setup_token_expires_at->isPast()) {
            return null;
        }

        return $member;
    }
}
