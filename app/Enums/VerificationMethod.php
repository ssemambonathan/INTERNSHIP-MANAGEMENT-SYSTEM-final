<?php

namespace App\Enums;

enum VerificationMethod: string
{
    case Gps = 'gps';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Gps => 'GPS',
            self::Manual => 'Manual review',
        };
    }
}
