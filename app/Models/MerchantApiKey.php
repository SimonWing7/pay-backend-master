<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MerchantApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'merchant_id',
        'name',
        'key_hash',
        'key_prefix',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Generate a new API key and return both the plaintext (show once) and the model data.
     */
    public static function generate(int $merchantId, string $name): array
    {
        $random    = bin2hex(random_bytes(32));          // 64 hex chars
        $plaintext = 'epk_live_' . $random;             // e.g. epk_live_a1b2c3...
        $hash      = hash('sha256', $plaintext);
        $prefix    = substr($plaintext, 0, 20) . '…';   // epk_live_a1b2c3d4e5…

        $key = static::create([
            'merchant_id' => $merchantId,
            'name'        => $name,
            'key_hash'    => $hash,
            'key_prefix'  => $prefix,
        ]);

        return ['key' => $key, 'plaintext' => $plaintext];
    }

    /**
     * Find a key model by its plaintext value (for API authentication).
     */
    public static function findByPlaintext(string $plaintext): ?static
    {
        $hash = hash('sha256', $plaintext);
        return static::where('key_hash', $hash)->whereNull('deleted_at')->first();
    }
}
