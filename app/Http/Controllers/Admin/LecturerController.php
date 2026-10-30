<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\Lecturer;
use App\Models\LecturerVisit;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $departmentId = $request->integer('department_id') ?: null;
        $status = (string) $request->query('status', '');

        $lecturers = Lecturer::query()
            ->with(['user', 'department.faculty'])
            ->withCount(['internships', 'internships as active_internships_count' => fn ($query) => $query->where('status', 'active'), 'visits'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($lecturerQuery) use ($search) {
                    $lecturerQuery->where('staff_number', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when(in_array($status, ['active', 'inactive', 'suspended'], true), fn ($query) => $query->whereHas('user', fn ($userQuery) => $userQuery->where('status', $status)))
            ->orderBy('staff_number')
            ->paginate(15)
            ->withQueryString();

        $departments = Department::query()->with('faculty')->orderBy('name')->get();

        $counts = [
            'total' => Lecturer::query()->count(),
            'active' => Lecturer::query()->whereHas('user', fn ($query) => $query->where('status', UserStatus::Active))->count(),
            'departments' => Lecturer::query()->distinct()->count('department_id'),
            'visits' => LecturerVisit::query()->count(),
        ];

        return view('admin.lecturers.index', compact('lecturers', 'departments', 'search', 'departmentId', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('admin.lecturers.create', [
            'departments' => Department::query()->with('faculty')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roleId = Role::query()->where('name', RoleName::Lecturer->value)->value('id');

        $lecturer = DB::transaction(function () use ($data, $roleId) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'role_id' => $roleId,
                'status' => $data['status'],
            ]);

            return $user->lecturer()->create([
                'department_id' => $data['department_id'],
                'staff_number' => $data['staff_number'],
                'position' => $data['position'] ?? null,
            ]);
        });

        $this->audit->record(
            'lecturer.created',
            'lecturers',
            sprintf('%s created the lecturer account for %s (%s).', $request->user()->name, $lecturer->user->name, $lecturer->staff_number),
            $lecturer,
            $request->user(),
        );

        return redirect()->route('admin.lecturers.show', $lecturer)->with('success', 'Lecturer account created.');
    }

    public function show(Lecturer $lecturer): View
    {
        $lecturer->load(['user', 'department.faculty']);

        $internships = $lecturer->internships()
            ->with(['student.user', 'student.department', 'company', 'period'])
            ->withCount(['dailyLogs', 'attendances'])
            ->orderByDesc('start_date')
            ->get();

        $visits = $lecturer->visits()
            ->with(['internship.student.user', 'internship.company'])
            ->orderByDesc('visit_date')
            ->limit(10)
            ->get();

        $evaluationsCount = Evaluation::query()
            ->whereIn('internship_id', $lecturer->internships()->pluck('id'))
            ->count();

        return view('admin.lecturers.show', compact('lecturer', 'internships', 'visits', 'evaluationsCount'));
    }

    public function edit(Lecturer $lecturer): View
    {
        $lecturer->load('user');

        return view('admin.lecturers.edit', [
            'lecturer' => $lecturer,
            'departments' => Department::query()->with('faculty')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $data = $this->validated($request, $lecturer);

        DB::transaction(function () use ($lecturer, $data) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'],
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = $data['password'];
            }

            $lecturer->user->update($userData);

            $lecturer->update([
                'department_id' => $data['department_id'],
                'staff_number' => $data['staff_number'],
                'position' => $data['position'] ?? null,
            ]);
        });

        $this->audit->record(
            'lecturer.updated',
            'lecturers',
            sprintf('%s updated the lecturer profile for %s (%s).', $request->user()->name, $lecturer->user->name, $lecturer->staff_number),
            $lecturer,
            $request->user(),
        );

        return redirect()->route('admin.lecturers.show', $lecturer)->with('success', 'Lecturer profile updated.');
    }

    public function toggleStatus(Request $request, Lecturer $lecturer): RedirectResponse
    {
        $newStatus = $lecturer->user->status === UserStatus::Active
            ? UserStatus::Suspended
            : UserStatus::Active;

        $lecturer->user->update(['status' => $newStatus]);

        $this->audit->record(
            'lecturer.status_changed',
            'lecturers',
            sprintf('%s set the account of %s to %s.', $request->user()->name, $lecturer->user->name, $newStatus->label()),
            $lecturer,
            $request->user(),
        );

        return redirect()->back()->with('success', 'Account status changed to '.$newStatus->label().'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Lecturer $lecturer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($lecturer?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$lecturer ? 'nullable' : 'required', 'string', 'min:8', 'max:72', 'confirmed'],
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'staff_number' => ['required', 'string', 'max:40', Rule::unique('lecturers', 'staff_number')->ignore($lecturer?->id)],
            'position' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::enum(UserStatus::class)],
        ]);
    }
}
