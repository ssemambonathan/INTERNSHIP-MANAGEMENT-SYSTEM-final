<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Enums\InternshipStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Internship;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');

        $companies = Company::query()
            ->withCount(['supervisors', 'internships'])
            ->withCount(['internships as active_internships_count' => fn ($query) => $query->where('status', 'active')])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($companyQuery) use ($search) {
                    $companyQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('industry', 'like', '%'.$search.'%')
                        ->orWhere('city', 'like', '%'.$search.'%');
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'total' => Company::query()->count(),
            'active' => Company::query()->where('status', CompanyStatus::Active)->count(),
            'supervisors' => CompanySupervisor::query()->count(),
            'placements' => Internship::query()->where('status', 'active')->count(),
        ];

        return view('admin.companies.index', compact('companies', 'search', 'status', 'counts'));
    }

    public function create(): View
    {
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $company = Company::create($data);

        $this->audit->record(
            'company.created',
            'companies',
            sprintf('%s registered the company %s.', $request->user()->name, $company->name),
            $company,
            $request->user(),
        );

        return redirect()->route('admin.companies.show', $company)->with('success', 'Company registered successfully.');
    }

    public function show(Company $company): View
    {
        $company->load([
            'supervisors.user',
            'internships' => fn ($query) => $query->with(['student.user', 'student.department', 'period', 'supervisor.user'])->orderByDesc('start_date'),
        ]);

        $activePlacements = $company->internships->where('status', InternshipStatus::Active)->count();
        $completedPlacements = $company->internships->where('status', InternshipStatus::Completed)->count();

        return view('admin.companies.show', compact('company', 'activePlacements', 'completedPlacements'));
    }

    public function edit(Company $company): View
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $this->validated($request, $company);

        $company->update($data);

        $this->audit->record(
            'company.updated',
            'companies',
            sprintf('%s updated the company profile for %s.', $request->user()->name, $company->name),
            $company,
            $request->user(),
        );

        return redirect()->route('admin.companies.show', $company)->with('success', 'Company details updated.');
    }

    public function toggleStatus(Request $request, Company $company): RedirectResponse
    {
        $newStatus = $company->status === CompanyStatus::Active ? CompanyStatus::Inactive : CompanyStatus::Active;

        $company->update(['status' => $newStatus]);

        $this->audit->record(
            'company.status_changed',
            'companies',
            sprintf('%s set %s to %s.', $request->user()->name, $company->name, $newStatus->label()),
            $company,
            $request->user(),
        );

        $message = $newStatus === CompanyStatus::Active
            ? 'Company activated. Students may check in at the registered location.'
            : 'Company deactivated. Attendance check-ins for this company are now blocked.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('companies', 'name')->ignore($company?->id)],
            'industry' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'allowed_radius' => ['required', 'integer', 'between:50,5000'],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
        ], [
            'latitude.between' => 'Latitude must be between -90 and 90 degrees.',
            'longitude.between' => 'Longitude must be between -180 and 180 degrees.',
            'allowed_radius.between' => 'The geofence radius must be between 50 and 5,000 metres.',
        ]);
    }
}
