<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Internship;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupervisorController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $companyId = $request->integer('company_id') ?: null;
        $status = (string) $request->query('status', '');

        $supervisors = CompanySupervisor::query()
            ->with(['user', 'company'])
            ->withCount(['internships', 'internships as active_internships_count' => fn ($query) => $query->where('status', 'active')])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('status', $status)))
            ->orderBy('company_id')
            ->paginate(15)
            ->withQueryString();

        $companies = Company::query()->orderBy('name')->get();

        $counts = [
            'total' => CompanySupervisor::query()->count(),
            'active' => CompanySupervisor::query()->whereHas('user', fn ($query) => $query->where('status', UserStatus::Active))->count(),
            'companies' => CompanySupervisor::query()->distinct()->count('company_id'),
            'placements' => Internship::query()->where('status', 'active')->count(),
        ];

        return view('admin.supervisors.index', compact('supervisors', 'companies', 'search', 'companyId', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('admin.supervisors.create', [
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roleId = Role::query()->where('name', RoleName::Supervisor->value)->value('id');

        $supervisor = DB::transaction(function () use ($data, $roleId) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role_id' => $roleId,
                'status' => $data['status'],
            ]);

            return $user->companySupervisor()->create([
                'company_id' => $data['company_id'],
                'position' => $data['position'] ?? null,
            ]);
        });

        $this->audit->record(
            'supervisor.created',
            'supervisors',
            sprintf('%s created the company supervisor account for %s.', $request->user()->name, $supervisor->user->name),
            $supervisor,
            $request->user(),
        );

        return redirect()->route('admin.supervisors.show', $supervisor)->with('success', 'Company supervisor account created.');
    }

    public function show(CompanySupervisor $supervisor): View
    {
        $supervisor->load(['user', 'company']);

        $internships = $supervisor->internships()
            ->with(['student.user', 'student.department', 'period'])
            ->withCount(['dailyLogs', 'attendances'])
            ->orderByDesc('start_date')
            ->get();

        $pendingLogs = $supervisor->internships()
            ->with('student.user')
            ->get()
            ->sum(fn ($internship) => $internship->dailyLogs()->pendingReview()->count());

        $evaluationsCount = $supervisor->evaluations()->count();

        return view('admin.supervisors.show', compact('supervisor', 'internships', 'pendingLogs', 'evaluationsCount'));
    }

    public function edit(CompanySupervisor $supervisor): View
    {
        $supervisor->load('user');

        return view('admin.supervisors.edit', [
            'supervisor' => $supervisor,
            'companies' => Company::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, CompanySupervisor $supervisor): RedirectResponse
    {
        $data = $this->validated($request, $supervisor);

        $hasActivePlacements = $supervisor->internships()
            ->whereIn('status', ['active', 'pending'])
            ->exists();

        if ($hasActivePlacements && (int) $data['company_id'] !== (int) $supervisor->company_id) {
            throw ValidationException::withMessages([
                'company_id' => 'This supervisor still has active or pending placements. Reassign those placements before changing the company.',
            ]);
        }

        DB::transaction(function () use ($supervisor, $data) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = $data['password'];
            }

            $supervisor->user->update($userData);

            $supervisor->update([
                'company_id' => $data['company_id'],
                'position' => $data['position'] ?? null,
            ]);
        });

        $this->audit->record(
            'supervisor.updated',
            'supervisors',
            sprintf('%s updated the company supervisor profile for %s.', $request->user()->name, $supervisor->user->name),
            $supervisor,
            $request->user(),
        );

        return redirect()->route('admin.supervisors.show', $supervisor)->with('success', 'Supervisor profile updated.');
    }

    public function toggleStatus(Request $request, CompanySupervisor $supervisor): RedirectResponse
    {
        $newStatus = $supervisor->user->status === UserStatus::Active
            ? UserStatus::Suspended
            : UserStatus::Active;

        $supervisor->user->update(['status' => $newStatus]);

        $this->audit->record(
            'supervisor.status_changed',
            'supervisors',
            sprintf('%s set the account of %s to %s.', $request->user()->name, $supervisor->user->name, $newStatus->label()),
            $supervisor,
            $request->user(),
        );

        return redirect()->back()->with('success', 'Account status changed to '.$newStatus->label().'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CompanySupervisor $supervisor = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($supervisor?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$supervisor ? 'nullable' : 'required', 'string', 'min:8', 'max:72', 'confirmed'],
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'position' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);
    }
}
