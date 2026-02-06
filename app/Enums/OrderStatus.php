<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case SENT_TO_KITCHEN = 'sent_to_kitchen';
    case PREPARING = 'preparing';
    case READY = 'ready';
    case SERVED = 'served';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case VOID = 'void';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SENT_TO_KITCHEN => 'Sent to Kitchen',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::SERVED => 'Served',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::VOID => 'Void',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::SENT_TO_KITCHEN => 'blue',
            self::PREPARING => 'orange',
            self::READY => 'green',
            self::SERVED => 'teal',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::VOID => 'red',
        };
    }
}
