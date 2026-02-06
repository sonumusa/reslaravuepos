<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::UNPAID => 'Unpaid',
            self::PARTIAL => 'Partial',
            self::PAID => 'Paid',
            self::REFUNDED => 'Refunded',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::UNPAID => 'red',
            self::PARTIAL => 'orange',
            self::PAID => 'green',
            self::REFUNDED => 'gray',
        };
    }
}
