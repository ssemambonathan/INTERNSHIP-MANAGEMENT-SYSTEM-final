<?php

namespace App\Policies;

use App\Models\DailyLog;
use App\Models\EvidenceFile;
use App\Models\LecturerVisit;
use App\Models\User;

class EvidenceFilePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function view(User $user, EvidenceFile $evidence): bool
    {
        $owner = $evidence->attachable;

        return $owner instanceof DailyLog
            ? $user->can('view', $owner)
            : ($owner instanceof LecturerVisit && $user->can('view', $owner));
    }

    public function delete(User $user, EvidenceFile $evidence): bool
    {
        $owner = $evidence->attachable;

        if ($owner instanceof DailyLog) {
            return $user->can('update', $owner) && $evidence->uploaded_by === $user->id;
        }

        if ($owner instanceof LecturerVisit) {
            return $user->can('uploadEvidence', $owner) && $evidence->uploaded_by === $user->id;
        }

        return false;
    }
}
