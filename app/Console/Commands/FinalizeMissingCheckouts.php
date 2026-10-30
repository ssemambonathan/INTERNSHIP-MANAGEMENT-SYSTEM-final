<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class FinalizeMissingCheckouts extends Command
{
    protected $signature = 'interntrack:finalize-checkouts';

    protected $description = 'Mark previous days without a check-out as incomplete attendance';

    public function handle(AttendanceService $attendance): int
    {
        $count = $attendance->finalizeMissingCheckouts();

        $this->info(sprintf('Attendance records marked incomplete: %d.', $count));

        return self::SUCCESS;
    }
}
