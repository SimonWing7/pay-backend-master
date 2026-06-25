<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case Initiated = 0;
    case Complete = 10;
    case Failed = 20;

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initiated',
            self::Complete => 'Complete',
            self::Failed => 'Failed',
        };
    }
}

