<?php

namespace App\Enums;

enum PraStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::SUCCESS => 'green',
            self::FAILED => 'red',
        };
    }
}
