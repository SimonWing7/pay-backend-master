<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReminder extends Model
{
    protected $fillable = [
        'product_id',
        'send_date',
        'message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'send_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
