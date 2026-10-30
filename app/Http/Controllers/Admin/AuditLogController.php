<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $module = trim((string) $request->query('module'));
        $action = trim((string) $request->query('action'));
        $userId = $request->integer('user_id') ?: null;
        $from = $this->parseDate($request->query('from'), now()->subDays(30));
        $to = $this->parseDate($request->query('to'), now());

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        $logs = AuditLog::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($logQuery) use ($search) {
                    $logQuery->where('description', 'like', '%'.$search.'%')
                        ->orWhere('action', 'like', '%'.$search.'%')
                        ->orWhere('record_type', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->whereDate('created_at', '>=', $from->toDateString())
            ->whereDate('created_at', '<=', $to->toDateString())
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $modules = AuditLog::query()->distinct()->orderBy('module')->pluck('module');
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');
        $users = User::query()->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id')->distinct())->orderBy('name')->get();

        $summary = [
            'total' => AuditLog::query()->count(),
            'today' => AuditLog::query()->whereDate('created_at', today())->count(),
            'filtered' => $logs->total(),
            'actors' => AuditLog::query()->whereNotNull('user_id')->distinct()->count('user_id'),
        ];

        return view('admin.audit-logs.index', compact(
            'logs',
            'modules',
            'actions',
            'users',
            'summary',
            'search',
            'module',
            'action',
            'userId',
            'from',
            'to',
        ));
    }

    private function parseDate(mixed $value, Carbon $default): Carbon
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
            } catch (\Throwable) {
                // Use the default below.
            }
        }

        return $default->copy()->startOfDay();
    }
}
