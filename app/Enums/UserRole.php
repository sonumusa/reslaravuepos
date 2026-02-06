<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case CASHIER = 'cashier';
    case WAITER = 'waiter';
    case KITCHEN = 'kitchen';

    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::CASHIER => 'Cashier',
            self::WAITER => 'Waiter',
            self::KITCHEN => 'Kitchen',
        };
    }
}
