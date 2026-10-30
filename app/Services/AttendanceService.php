<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        private readonly GeolocationService $geolocation,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function checkIn(Student $student, float $latitude, float $longitude, ?float $accuracy = null): Attendance
    {
        $this->assertCoordinates($latitude, $longitude);

        $today = now()->startOfDay();
        $internship = $this->activeInternshipFor($student, $today);

        if (! $internship) {
            throw ValidationException::withMessages([
                'attendance' => 'You do not have an active internship placement covering today. Contact the internship office.',
            ]);
        }

        if (! $internship->period->isActive()) {
            throw ValidationException::withMessages([
                'attendance' => 'Your internship period is not currently open for attendance.',
            ]);
        }

        if ($internship->attendances()->whereDate('date', $today->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'attendance' => 'You have already checked in today. Duplicate check-ins are not allowed.',
            ]);
        }

        $company = $internship->company;
        $distance = $this->geolocation->distanceInMetres($latitude, $longitude, $company->latitude, $company->longitude);
        $radius = $company->allowed_radius ?: (int) settings('default_gps_radius', 150);
        $outside = $distance > $radius;

        $now = now();
        $lateAfter = $this->lateAfter($now);
        $lateMinutes = $now->greaterThan($lateAfter) ? $lateAfter->diffInMinutes($now) : 0;

        $status = $outside
            ? AttendanceStatus::OutsideLocation
            : ($lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present);

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'date' => $today->toDateString(),
            'check_in_time' => $now,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => $accuracy,
            'distance_from_company' => $distance,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'verification_method' => VerificationMethod::Gps,
            'notes' => $outside
                ? sprintf('Check-in was %.0f m from the company; allowed radius is %d m.', $distance, $radius)
                : null,
        ]);

        $this->audit->record(
            'attendance.check_in',
            'attendance',
            sprintf('%s checked in %s (distance %.0f m, status %s).', $student->user->name, $now->format('H:i'), $distance, $status->label()),
            $attendance,
            $student->user,
        );

        if ($outside) {
            $this->notifications->notifyAttendanceFlagged($attendance);
        }

        return $attendance;
    }

    public function checkOut(Student $student, float $latitude, float $longitude, ?float $accuracy = null): Attendance
    {
        $this->assertCoordinates($latitude, $longitude);

        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'attendance' => 'You must check in before you can check out.',
            ]);
        }

        if ($attendance->hasCheckedOut()) {
            throw ValidationException::withMessages([
                'attendance' => 'You have already checked out today. Duplicate check-outs are not allowed.',
            ]);
        }

        if ($attendance->status === AttendanceStatus::Rejected) {
            throw ValidationException::withMessages([
                'attendance' => 'This attendance record was rejected and cannot be checked out.',
            ]);
        }

        $company = $attendance->internship->company;
        $distance = $this->geolocation->distanceInMetres($latitude, $longitude, $company->latitude, $company->longitude);
        $now = now();
        $workedMinutes = max(0, (int) $attendance->check_in_time->diffInMinutes($now));
        $minimumMinutes = (int) round(((float) settings('minimum_working_hours', 7.5)) * 60);

        $status = $attendance->status;
        $notes = $attendance->notes;

        if ($status !== AttendanceStatus::OutsideLocation && $workedMinutes < $minimumMinutes) {
            $status = AttendanceStatus::Incomplete;
            $notes = trim(($notes ? $notes.' ' : '').sprintf(
                'Worked %dh %02dm, below the required minimum of %.1f hours.',
                intdiv($workedMinutes, 60),
                $workedMinutes % 60,
                $minimumMinutes / 60,
            ));
        }

        $attendance->update([
            'check_out_time' => $now,
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => $accuracy,
            'check_out_distance' => $distance,
            'worked_minutes' => $workedMinutes,
            'status' => $status,
            'notes' => $notes,
        ]);

        $this->audit->record(
            'attendance.check_out',
            'attendance',
            sprintf('%s checked out %s (worked %dh %02dm).', $student->user->name, $now->format('H:i'), intdiv($workedMinutes, 60), $workedMinutes % 60),
            $attendance,
            $student->user,
        );

        if ($status === AttendanceStatus::Incomplete && $attendance->wasChanged('status')) {
            $this->notifications->notifyAttendanceFlagged($attendance);
        }

        return $attendance->refresh();
    }

    public function review(Attendance $attendance, User $reviewer, string $decision, ?string $reason = null): Attendance
    {
        if (! in_array($decision, ['verify', 'reject'], true)) {
            throw ValidationException::withMessages(['decision' => 'Invalid review decision.']);
        }

        if ($decision === 'reject' && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required when rejecting attendance.']);
        }

        if ($attendance->status === AttendanceStatus::Rejected) {
            throw ValidationException::withMessages(['attendance' => 'This attendance record has already been rejected.']);
        }

        $status = $decision === 'verify'
            ? (($attendance->late_minutes ?? 0) > 0 ? AttendanceStatus::Late : AttendanceStatus::Present)
            : AttendanceStatus::Rejected;

        $note = $decision === 'verify'
            ? sprintf('Manually verified by %s.', $reviewer->name)
            : sprintf('Rejected by %s: %s', $reviewer->name, $reason);

        $attendance->update([
            'status' => $status,
            'verification_method' => VerificationMethod::Manual,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => trim(($attendance->notes ? $attendance->notes.' ' : '').$note),
        ]);

        $attendance->approvals()->create([
            'reviewer_id' => $reviewer->id,
            'action' => $decision === 'verify' ? 'verified' : 'rejected',
            'comments' => $reason,
        ]);

        $this->audit->record(
            'attendance.'.$decision,
            'attendance',
            sprintf('%s %s attendance for %s on %s.', $reviewer->name, $decision === 'verify' ? 'verified' : 'rejected', $attendance->student->user->name, $attendance->date->format('d M Y')),
            $attendance,
            $reviewer,
        );

        $this->notifications->notifyAttendanceReviewed($attendance, $decision, $reason);

        return $attendance->refresh();
    }

    public function finalizeMissingCheckouts(): int
    {
        $records = Attendance::query()
            ->whereDate('date', '<', now()->toDateString())
            ->whereNull('check_out_time')
            ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])
            ->get();

        foreach ($records as $record) {
            $record->update([
                'status' => AttendanceStatus::Incomplete,
                'notes' => trim(($record->notes ? $record->notes.' ' : '').'No check-out was recorded for this day.'),
            ]);
        }

        return $records->count();
    }

    private function activeInternshipFor(Student $student, CarbonInterface $date): ?Internship
    {
        return Internship::query()
            ->with(['company', 'period'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->latest('start_date')
            ->first();
    }

    private function lateAfter(CarbonInterface $date): Carbon
    {
        $time = settings('late_after', '08:30');

        return Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d').' '.$time);
    }

    private function assertCoordinates(float $latitude, float $longitude): void
    {
        if (! $this->geolocation->isValidCoordinate($latitude, $longitude)) {
            throw ValidationException::withMessages([
                'location' => 'The GPS coordinates received are invalid. Please enable precise location and try again.',
            ]);
        }
    }
}
