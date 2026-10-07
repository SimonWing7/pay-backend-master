<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case Initiated = 0;
    case Complete = 10;
    case Failed = 20;
    // Customer started checkout but never finished it — no final answer ever
    // came back from the bank/Lean, as opposed to Failed, which is a
    // definitive failure/rejection.
    case Abandoned = 30;

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initiated',
            self::Complete => 'Complete',
            self::Failed => 'Failed',
            self::Abandoned => 'Abandoned',
        };
    }
}
