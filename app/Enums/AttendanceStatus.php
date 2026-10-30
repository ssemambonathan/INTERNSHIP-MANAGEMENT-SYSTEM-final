<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case OutsideLocation = 'outside_location';
    case Incomplete = 'incomplete';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Late => 'Late',
            self::OutsideLocation => 'Outside Location',
            self::Incomplete => 'Incomplete',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late => 'warning',
            self::OutsideLocation => 'danger',
            self::Incomplete => 'info',
            self::Rejected => 'dark',
        };
    }

    public function countsAsAttended(): bool
    {
        return in_array($this, [self::Present, self::Late], true);
    }

    public function requiresReview(): bool
    {
        return in_array($this, [self::OutsideLocation, self::Incomplete], true);
    }
}
