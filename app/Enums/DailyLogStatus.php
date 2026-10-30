<?php

namespace App\Enums;

enum DailyLogStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'info',
            self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isEditableByStudent(): bool
    {
        return in_array($this, [self::Draft, self::Rejected], true);
    }

    public function isPendingReview(): bool
    {
        return in_array($this, [self::Submitted, self::UnderReview], true);
    }
}
