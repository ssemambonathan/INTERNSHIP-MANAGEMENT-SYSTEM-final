<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function show(Request $request, ReportService $reports): View|RedirectResponse
    {
        $internship = $this->internshipFor($request);

        if (! $internship) {
            return redirect()->route('student.dashboard')->with('error', 'You need an internship placement before a report can be generated.');
        }

        return view('reports.student', [
            'report' => $reports->studentReport($internship),
            'backRoute' => route('student.logs.index'),
            'downloadRoute' => route('student.report.download'),
            'viewer' => 'student',
        ]);
    }

    public function download(Request $request, ReportService $reports): Response|RedirectResponse
    {
        $internship = $this->internshipFor($request);

        if (! $internship) {
            return redirect()->route('student.dashboard')->with('error', 'You need an internship placement before a report can be generated.');
        }

        $report = $reports->studentReport($internship);

        $filename = Str::slug($request->user()->name).'-internship-report.pdf';

        return Pdf::loadView('pdf.student-report', ['report' => $report])
            ->setPaper('a4')
            ->download($filename);
    }

    private function internshipFor(Request $request): ?Internship
    {
        $student = $request->user()->student;

        abort_unless($student, 403, 'Your student profile is not linked to this account.');

        return $student->internships()
            ->orderByRaw("FIELD(status, 'active', 'pending', 'completed', 'cancelled')")
            ->orderByDesc('start_date')
            ->first();
    }
}
