<?php

namespace App\Policies;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use App\Models\User;

class DailyLogPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, DailyLog $log): bool
    {
        if ($user->isStudent() && $user->student) {
            return $log->student_id === $user->student->id;
        }

        if ($user->isSupervisor() && $user->companySupervisor) {
            return $log->internship->company_supervisor_id === $user->companySupervisor->id;
        }

        if ($user->isLecturer() && $user->lecturer) {
            return $log->internship->lecturer_id === $user->lecturer->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isStudent() && $user->student !== null;
    }

    public function update(User $user, DailyLog $log): bool
    {
        return $user->isStudent()
            && $user->student
            && $log->student_id === $user->student->id
            && $log->isEditableByStudent();
    }

    public function delete(User $user, DailyLog $log): bool
    {
        return $user->isStudent()
            && $user->student
            && $log->student_id === $user->student->id
            && $log->status === DailyLogStatus::Draft;
    }

    public function submit(User $user, DailyLog $log): bool
    {
        return $this->update($user, $log);
    }

    public function uploadEvidence(User $user, DailyLog $log): bool
    {
        return $this->update($user, $log);
    }

    public function review(User $user, DailyLog $log): bool
    {
        if (! $user->isSupervisor() || ! $user->companySupervisor) {
            return false;
        }

        return $log->internship->company_supervisor_id === $user->companySupervisor->id;
    }
}
