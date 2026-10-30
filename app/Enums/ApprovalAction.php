<?php

namespace App\Enums;

enum ApprovalAction: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
    case CorrectionRequested = 'correction_requested';
    case Commented = 'commented';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::CorrectionRequested => 'Correction requested',
            self::Commented => 'Comment',
            self::Verified => 'Verified',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Approved, self::Verified => 'success',
            self::Rejected => 'danger',
            self::CorrectionRequested => 'warning',
            self::Commented => 'info',
        };
    }
}
