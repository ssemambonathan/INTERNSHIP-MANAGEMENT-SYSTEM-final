<?php

namespace App\Enums;

enum InternshipStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Completed => 'primary',
            self::Cancelled => 'secondary',
        };
    }
}
