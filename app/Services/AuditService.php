<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function record(
        string $action,
        string $module,
        string $description,
        ?Model $record = null,
        ?User $actor = null,
    ): AuditLog {
        $request = app()->bound('request') ? request() : null;

        return AuditLog::create([
            'user_id' => $actor?->getKey() ?? auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_type' => $record ? $record->getMorphClass() : null,
            'record_id' => $record?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? substr($request->userAgent(), 0, 512) : null,
        ]);
    }
}
