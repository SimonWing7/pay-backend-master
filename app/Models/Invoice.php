<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'total_fee',
        'consumer_id',
        'merchant_id',
        'merchant_entity_id',
        'status',
        'uuid',
        'return_url',
        'cancel_url',
        'reference',
        'link_type',
        'link_email_status',
        'link_emailed_at',
        'link_email_error',
        'custom_fields',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->uuid)) {
                $invoice->uuid = (string) Str::uuid();
            }
            if (!isset($invoice->status)) {
                $invoice->status = InvoiceStatus::Draft;
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_fee' => 'double',
            'status' => InvoiceStatus::class,
            'custom_fields' => 'array',
            'link_emailed_at' => 'datetime',
        ];
    }

    /**
     * Add query parameters to a URL, keeping any it already has.
     *
     * @param array<string, string> $params
     */
    public static function appendQuery(string $url, array $params): string
    {
        $parsed   = parse_url($url);
        $existing = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $existing);
        }
        $merged = array_merge($existing, $params);

        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        if (!empty($parsed['port'])) {
            $base .= ':' . $parsed['port'];
        }
        if (!empty($parsed['path'])) {
            $base .= $parsed['path'];
        }

        return $base . '?' . http_build_query($merged);
    }

    /** Schemes that must never be put in a link on our page. */
    public static function isScriptUrl(string $url): bool
    {
        return (bool) preg_match('/^\s*(javascript|data|vbscript):/i', $url);
    }

    /**
     * Where the "choose another payment method" button sends the customer: the
     * merchant's own cancel_url, marked as cancelled so their handler can undo
     * the order and put the cart back. Null when the link has no cancel_url.
     */
    public function cancelReturnUrl(): ?string
    {
        if (empty($this->cancel_url) || self::isScriptUrl($this->cancel_url)) {
            return null;
        }

        return self::appendQuery($this->cancel_url, ['status' => 'cancelled', 'payment_link_id' => $this->uuid]);
    }

    /**
     * Get the consumer that owns the invoice.
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class);
    }

    /**
     * Get the merchant that owns the invoice.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantEntity()
    {
        return $this->belongsTo(MerchantEntity::class);
    }

    /**
     * Get the invoice details for the invoice.
     */
    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    /**
     * Get the app user payments for the invoice.
     */
    public function appUserPayments()
    {
        return $this->hasMany(AppUserPayment::class);
    }

    /**
     * Get the groups for the invoice.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'invoice_group');
    }
}

