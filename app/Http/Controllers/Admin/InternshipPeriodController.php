<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\InternshipPeriod;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InternshipPeriodController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');
        $academicYearId = $request->integer('academic_year_id') ?: null;

        $periods = InternshipPeriod::query()
            ->with('academicYear')
            ->withCount('internships')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when(in_array($status, ['draft', 'active', 'closed'], true), fn ($query) => $query->where('status', $status))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get();

        $counts = InternshipPeriod::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.periods.index', compact('periods', 'academicYears', 'counts', 'search', 'status', 'academicYearId'));
    }

    public function create(): View
    {
        return view('admin.periods.create', [
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $period = InternshipPeriod::create($data);

        $this->audit->record(
            'period.created',
            'periods',
            sprintf('%s created the internship period %s.', $request->user()->name, $period->name),
            $period,
            $request->user(),
        );

        return redirect()->route('admin.periods.index')->with('success', 'Internship period created.');
    }

    public function edit(InternshipPeriod $period): View
    {
        return view('admin.periods.edit', [
            'period' => $period,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $data = $this->validated($request, $period);

        $period->update($data);

        $this->audit->record(
            'period.updated',
            'periods',
            sprintf('%s updated the internship period %s.', $request->user()->name, $period->name),
            $period,
            $request->user(),
        );

        return redirect()->route('admin.periods.index')->with('success', 'Internship period updated.');
    }

    public function toggleStatus(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $newStatus = match ($period->status) {
            PeriodStatus::Draft => PeriodStatus::Active,
            PeriodStatus::Active => PeriodStatus::Closed,
            PeriodStatus::Closed => PeriodStatus::Active,
        };

        if ($newStatus === PeriodStatus::Active) {
            $overlap = InternshipPeriod::query()
                ->where('id', '!=', $period->id)
                ->where('status', PeriodStatus::Active)
                ->whereDate('start_date', '<=', $period->end_date)
                ->whereDate('end_date', '>=', $period->start_date)
                ->exists();

            if ($overlap) {
                return redirect()->back()->with('error', 'Another active internship period overlaps these dates. Close it before activating this period.');
            }
        }

        $previous = $period->status;
        $period->update(['status' => $newStatus]);

        $this->audit->record(
            'period.status_changed',
            'periods',
            sprintf('%s changed the period %s from %s to %s.', $request->user()->name, $period->name, $previous->label(), $newStatus->label()),
            $period,
            $request->user(),
        );

        $message = $newStatus === PeriodStatus::Closed
            ? 'Internship period closed. Students can no longer check in.'
            : 'Internship period is now '.strtolower($newStatus->label()).'.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?InternshipPeriod $period = null): array
    {
        $data = $request->validate([
            'academic_year_id' => ['required', 'integer', Rule::exists('academic_years', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(PeriodStatus::class)],
        ]);

        $year = AcademicYear::query()->find($data['academic_year_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        if ($year && ($start->lt($year->start_date) || $end->gt($year->end_date))) {
            throw ValidationException::withMessages([
                'start_date' => 'The period dates must fall within the academic year '.$year->name.' ('
                    .$year->start_date->format('d M Y').' – '.$year->end_date->format('d M Y').').',
            ]);
        }

        $overlap = InternshipPeriod::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->when($period, fn ($query) => $query->whereKeyNot($period->getKey()))
            ->whereDate('start_date', '<=', $data['end_date'])
            ->whereDate('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'start_date' => 'Another internship period in this academic year overlaps these dates.',
            ]);
        }

        if ($period) {
            $earliest = $period->internships()->min('start_date');
            $latest = $period->internships()->max('end_date');

            if ($earliest && $start->gt(Carbon::parse($earliest))) {
                throw ValidationException::withMessages([
                    'start_date' => 'The start date cannot be after placements already recorded in this period.',
                ]);
            }

            if ($latest && $end->lt(Carbon::parse($latest))) {
                throw ValidationException::withMessages([
                    'end_date' => 'The end date cannot be before placements already recorded in this period.',
                ]);
            }
        }

        return $data;
    }
}
