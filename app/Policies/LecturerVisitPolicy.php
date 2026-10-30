<?php

namespace App\Policies;

use App\Models\LecturerVisit;
use App\Models\User;

class LecturerVisitPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->isLecturer() && $user->lecturer !== null;
    }

    public function view(User $user, LecturerVisit $visit): bool
    {
        if ($user->isStudent() && $user->student) {
            return $visit->internship->student_id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $visit->internship->company_supervisor_id === $user->companySupervisor->id;
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $visit->internship->lecturer_id === $user->lecturer->id;
        }

        return false;
    }

    public function update(User $user, LecturerVisit $visit): bool
    {
        return $user->isLecturer()
            && $user->lecturer
            && $visit->lecturer_id === $user->lecturer->id
            && $visit->status->value === 'scheduled';
    }

    public function delete(User $user, LecturerVisit $visit): bool
    {
        return $this->update($user, $visit);
    }

    public function uploadEvidence(User $user, LecturerVisit $visit): bool
    {
        return $user->isLecturer()
            && $user->lecturer
            && $visit->lecturer_id === $user->lecturer->id
            && $visit->status->value !== 'cancelled';
    }
}
