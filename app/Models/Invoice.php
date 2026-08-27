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
        ];
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

