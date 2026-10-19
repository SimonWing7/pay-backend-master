<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceDetail extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'product_id',
        'consumer_id',
        'fee',
        'title',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'double',
        ];
    }

    /**
     * Get the invoice that owns the invoice detail.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * The child/individual this line item is for (set on bulk invoices).
     * Includes soft-deleted so a removed person's name stays on old invoices.
     */
    public function consumer()
    {
        return $this->belongsTo(Consumer::class)->withTrashed();
    }

    /**
     * Get the product for the invoice detail.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

