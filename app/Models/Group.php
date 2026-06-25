<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'merchant_id',
        'name',
        'color',
    ];

    /**
     * Get the merchant that owns the group.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the consumers for the group.
     */
    public function consumers()
    {
        return $this->belongsToMany(Consumer::class, 'consumer_group');
    }

    /**
     * Get the invoices for the group.
     */
    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_group');
    }
}
