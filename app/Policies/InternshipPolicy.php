<?php

namespace App\Policies;

use App\Models\Internship;
use App\Models\User;

class InternshipPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Internship $internship): bool
    {
        if ($user->isStudent() && $user->student) {
            return $internship->student_id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $internship->company_supervisor_id === $user->companySupervisor->id;
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $internship->lecturer_id === $user->lecturer->id;
        }

        return false;
    }

    public function update(User $user, Internship $internship): bool
    {
        return false;
    }

    public function evaluate(User $user, Internship $internship): bool
    {
        return $user->isSupervisor()
            && $user->companySupervisor
            && $internship->company_supervisor_id === $user->companySupervisor->id
            && $internship->isActive();
    }

    public function recordVisit(User $user, Internship $internship): bool
    {
        return $user->isLecturer()
            && $user->lecturer
            && $internship->lecturer_id === $user->lecturer->id
            && $internship->isActive();
    }

    public function reopenLogDate(User $user, Internship $internship): bool
    {
        return $user->isLecturer()
            && $user->lecturer
            && $internship->lecturer_id === $user->lecturer->id;
    }
}
