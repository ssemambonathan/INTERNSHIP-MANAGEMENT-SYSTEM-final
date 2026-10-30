<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\Student;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function show(Request $request, Student $student, ReportService $reports): View
    {
        $internship = $this->internshipFor($request, $student);

        return view('reports.student', [
            'report' => $reports->studentReport($internship, true),
            'backRoute' => route('lecturer.students.show', $student),
            'downloadRoute' => route('lecturer.reports.student.download', $student),
            'viewer' => 'lecturer',
        ]);
    }

    public function download(Request $request, Student $student, ReportService $reports): Response
    {
        $internship = $this->internshipFor($request, $student);
        $report = $reports->studentReport($internship, true);

        $filename = Str::slug($student->name).'-internship-report.pdf';

        return Pdf::loadView('pdf.student-report', ['report' => $report])
            ->setPaper('a4')
            ->download($filename);
    }

    private function internshipFor(Request $request, Student $student): Internship
    {
        $lecturer = $request->user()->lecturer;

        abort_unless($lecturer, 403, 'Your lecturer profile is not linked to this account.');

        Gate::authorize('view', $student);

        $internship = $student->internships()
            ->where('lecturer_id', $lecturer->id)
            ->orderByRaw("FIELD(status, 'active', 'pending', 'completed', 'cancelled')")
            ->orderByDesc('start_date')
            ->first();

        abort_unless($internship, 404, 'This student is not assigned to you.');

        return $internship;
    }
}
