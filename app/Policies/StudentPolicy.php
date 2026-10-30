<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Student $student): bool
    {
        if ($user->isStudent() && $user->student) {
            return $student->id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $student->internships()
                ->where('company_supervisor_id', $user->companySupervisor->id)
                ->exists();
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $student->internships()
                ->where('lecturer_id', $user->lecturer->id)
                ->exists();
        }

        return false;
    }
}
