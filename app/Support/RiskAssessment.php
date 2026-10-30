<?php

namespace App\Support;

use App\Enums\RiskLevel;
use Illuminate\Support\Carbon;

class RiskAssessment
{
    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(
        public RiskLevel $level,
        public array $reasons,
        public float $attendancePercentage,
        public int $expectedDays,
        public int $attendedDays,
        public int $logsSubmitted,
        public int $pendingLogs,
        public int $missedLogs,
        public int $rejectedLogs,
        public int $incompleteCheckouts,
        public int $consecutiveAbsences,
        public bool $supervisorActive,
        public ?Carbon $lastAttendanceDate,
    ) {}

    public function hasReasons(): bool
    {
        return $this->reasons !== [];
    }
}
