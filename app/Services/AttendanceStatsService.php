<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Internship;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceStatsService
{
    /** @return array<int, int> */
    public function workingDays(): array
    {
        $days = settings('working_days', [1, 2, 3, 4, 5]);

        return is_array($days) ? array_map('intval', $days) : [1, 2, 3, 4, 5];
    }

    public function isWorkingDay(CarbonInterface $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->workingDays(), true);
    }

    /**
     * Expected working days for an internship up to a cut-off date (inclusive).
     */
    public function expectedWorkingDays(Internship $internship, ?CarbonInterface $until = null): int
    {
        $start = $internship->start_date->copy()->startOfDay();
        $end = $internship->end_date->copy()->startOfDay();
        $cutoff = ($until?->copy() ?? now())->startOfDay();
        $end = $end->lt($cutoff) ? $end : $cutoff;

        if ($start->gt($end)) {
            return 0;
        }

        $count = 0;

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->isWorkingDay($date)) {
                $count++;
            }
        }

        return $count;
    }

    /** @return Collection<int, Carbon> */
    public function expectedWorkingDates(Internship $internship, ?CarbonInterface $until = null): Collection
    {
        $start = $internship->start_date->copy()->startOfDay();
        $end = $internship->end_date->copy()->startOfDay();
        $cutoff = ($until?->copy() ?? now())->startOfDay();
        $end = $end->lt($cutoff) ? $end : $cutoff;
        $dates = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($this->isWorkingDay($date)) {
                $dates->push($date->copy());
            }
        }

        return $dates;
    }

    public function attendedDays(Internship $internship, ?CarbonInterface $until = null): int
    {
        return $internship->attendances()
            ->attended()
            ->when($until, fn ($query) => $query->whereDate('date', '<=', $until->toDateString()))
            ->count();
    }

    public function attendancePercentage(Internship $internship, ?CarbonInterface $until = null): float
    {
        $expected = $this->expectedWorkingDays($internship, $until);

        if ($expected === 0) {
            return 0.0;
        }

        return round(min(100, ($this->attendedDays($internship, $until) / $expected) * 100), 1);
    }

    /**
     * @param  Collection<int, Internship>  $internships
     */
    public function averagePercentage(Collection $internships): float
    {
        $percentages = $internships
            ->filter(fn (Internship $internship) => $this->expectedWorkingDays($internship) > 0)
            ->map(fn (Internship $internship) => $this->attendancePercentage($internship));

        return $percentages->isEmpty() ? 0.0 : round($percentages->avg(), 1);
    }

    /**
     * Consecutive missed working days ending yesterday.
     */
    public function consecutiveAbsences(Internship $internship): int
    {
        $attendedDates = $internship->attendances()
            ->attended()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $cursor = now()->startOfDay()->subDay();
        $missed = 0;

        while ($cursor->gte($internship->start_date)) {
            if ($this->isWorkingDay($cursor)) {
                if ($attendedDates->has($cursor->toDateString())) {
                    break;
                }

                $missed++;
            }

            $cursor->subDay();
        }

        return $missed;
    }

    public function lastAttendanceDate(Internship $internship): ?Carbon
    {
        $date = $internship->attendances()->attended()->max('date');

        return $date ? Carbon::parse($date) : null;
    }

    public function missedLogDates(Internship $internship, ?CarbonInterface $since = null): Collection
    {
        $loggedDates = $internship->dailyLogs()
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        return $internship->attendances()
            ->attended()
            ->when($since, fn ($query) => $query->whereDate('date', '>=', $since->toDateString()))
            ->orderBy('date')
            ->get()
            ->filter(fn ($attendance) => ! $loggedDates->has($attendance->date->toDateString()))
            ->map(fn ($attendance) => $attendance->date->copy())
            ->values();
    }

    /**
     * Attendance trend for a set of internships, keyed by date.
     *
     * @param  Collection<int, Internship>  $internships
     * @return array{labels: array<int, string>, present: array<int, int>, late: array<int, int>, flagged: array<int, int>}
     */
    public function attendanceTrend(Collection $internships, int $days = 14): array
    {
        $internshipIds = $internships->pluck('id');
        $start = now()->startOfDay()->subDays($days - 1);
        $records = Attendance::query()
            ->whereIn('internship_id', $internshipIds)
            ->whereDate('date', '>=', $start->toDateString())
            ->get()
            ->groupBy(fn ($attendance) => $attendance->date->toDateString());

        $labels = [];
        $present = [];
        $late = [];
        $flagged = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('d M');
            $dayRecords = $records->get($date->toDateString(), collect());
            $present[] = $dayRecords->where('status', AttendanceStatus::Present)->count();
            $late[] = $dayRecords->where('status', AttendanceStatus::Late)->count();
            $flagged[] = $dayRecords->filter(fn ($record) => $record->status->requiresReview())->count();
        }

        return compact('labels', 'present', 'late', 'flagged');
    }
}
