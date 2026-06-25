<?php

namespace App\Enums;

enum InvoiceStatus: int
{
    case Draft = 0;
    case Paid = 10;
    case Failed = 20;

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
        };
    }
}

