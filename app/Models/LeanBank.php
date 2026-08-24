<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeanBank extends Model
{
    protected $fillable = [
        'identifier',
        'name',
        'logo_url',
        'connection_type',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];
}
