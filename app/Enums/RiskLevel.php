<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Normal = 'normal';
    case Warning = 'warning';
    case HighRisk = 'high_risk';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Warning => 'Warning',
            self::HighRisk => 'High Risk',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Normal => 'success',
            self::Warning => 'warning',
            self::HighRisk => 'danger',
        };
    }

    public function weight(): int
    {
        return match ($this) {
            self::Normal => 0,
            self::Warning => 1,
            self::HighRisk => 2,
        };
    }
}
