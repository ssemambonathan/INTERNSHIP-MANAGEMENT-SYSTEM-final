<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->isStudent() && $user->student) {
            return $attendance->student_id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $attendance->internship->company_supervisor_id === $user->companySupervisor->id;
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $attendance->internship->lecturer_id === $user->lecturer->id;
        }

        return false;
    }

    public function checkIn(User $user): bool
    {
        return $user->isStudent() && $user->student !== null;
    }

    public function checkOut(User $user): bool
    {
        return $user->isStudent() && $user->student !== null;
    }

    public function review(User $user, Attendance $attendance): bool
    {
        if (! $user->isSupervisor() || ! $user->companySupervisor) {
            return false;
        }

        return $attendance->internship->company_supervisor_id === $user->companySupervisor->id
            && $attendance->status->requiresReview();
    }
}
