<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Evaluation;
use App\Models\LecturerVisit;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_student_can_view_and_download_their_report(): void
    {
        $placement = $this->createPlacement();
        $this->seedReportData($placement);

        $this->actingAs($placement['studentUser'])
            ->get(route('student.report.show'))
            ->assertOk()
            ->assertSee($placement['studentUser']->name);

        $response = $this->actingAs($placement['studentUser'])
            ->get(route('student.report.download'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_assigned_lecturer_can_view_and_download_a_student_report(): void
    {
        $placement = $this->createPlacement();
        $this->seedReportData($placement);

        $this->actingAs($placement['lecturerUser'])
            ->get(route('lecturer.reports.student', $placement['student']))
            ->assertOk()
            ->assertSee($placement['studentUser']->name);

        $response = $this->actingAs($placement['lecturerUser'])
            ->get(route('lecturer.reports.student.download', $placement['student']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_admin_report_page_and_pdf_export_include_placement_data(): void
    {
        $placement = $this->createPlacement();
        $this->seedReportData($placement);
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->get(route('admin.reports.index', [
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'company_id' => $this->company->id,
        ]))
            ->assertOk()
            ->assertSee($placement['studentUser']->name)
            ->assertSee('Average attendance');

        $response = $this->actingAs($admin)->get(route('admin.reports.export', [
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'company_id' => $this->company->id,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_report_attendance_is_capped_at_today_for_active_placements(): void
    {
        $placement = $this->createPlacement();
        $this->createAttendanceRecord($placement['internship'], [
            'date' => '2026-10-20',
            'status' => AttendanceStatus::Present,
        ]);

        $report = app(ReportService::class)->universityReport(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
            ['company_id' => $this->company->id],
        );

        $row = $report['rows']->firstWhere('internship.id', $placement['internship']->id);

        // Frozen at 21 Oct 2026: expected days run from 1 Sep to today, not to 31 Dec.
        $this->assertSame(37, $row['expectedDays']);
        $this->assertSame(1, $row['attendedDays']);
        $this->assertEqualsWithDelta(2.7, $row['attendancePercentage'], 0.1);
    }

    public function test_admin_report_rejects_invalid_filter_dates(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->get(route('admin.reports.index', [
            'from' => '2026-12-31',
            'to' => '2026-01-01',
        ]))->assertSessionHasErrors('to');
    }

    private function seedReportData(array $placement): void
    {
        $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Approved,
            'submitted_at' => now()->subDays(2),
            'reviewed_at' => now()->subDay(),
        ]);

        Evaluation::create([
            'internship_id' => $placement['internship']->id,
            'company_supervisor_id' => $placement['supervisor']->id,
            'week_start' => '2026-10-12',
            'week_end' => '2026-10-18',
            'technical_skills' => 4,
            'communication' => 4,
            'professionalism' => 5,
            'teamwork' => 4,
            'punctuality' => 4,
            'initiative' => 3,
            'strengths' => 'Consistent delivery.',
            'areas_for_improvement' => 'Document decisions earlier.',
            'remarks' => 'On track.',
            'recommendation' => 'Continue',
        ]);

        LecturerVisit::create([
            'internship_id' => $placement['internship']->id,
            'lecturer_id' => $placement['lecturer']->id,
            'visit_date' => '2026-10-14',
            'purpose' => 'Mid-placement supervision visit.',
            'status' => 'completed',
            'observations' => 'Student is progressing well with the assigned module.',
            'completed_at' => now(),
        ]);
    }
}
