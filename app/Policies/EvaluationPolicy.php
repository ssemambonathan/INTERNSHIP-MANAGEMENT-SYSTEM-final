<?php

namespace App\Policies;

use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, Evaluation $evaluation): bool
    {
        if ($user->isStudent() && $user->student) {
            return $evaluation->internship->student_id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $evaluation->company_supervisor_id === $user->companySupervisor->id;
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $evaluation->internship->lecturer_id === $user->lecturer->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isSupervisor() && $user->companySupervisor !== null;
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return $user->isSupervisor()
            && $user->companySupervisor
            && $evaluation->company_supervisor_id === $user->companySupervisor->id
            && $evaluation->internship->isActive();
    }
}
