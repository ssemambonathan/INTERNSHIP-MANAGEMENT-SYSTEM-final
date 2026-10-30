<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Enums\RiskLevel;
use App\Services\RiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class RiskTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_todays_attendance_without_a_log_is_not_counted_as_missed_yet(): void
    {
        $placement = $this->createPlacement(internshipAttributes: [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-21',
        ]);

        $this->createAttendanceRecord($placement['internship'], ['date' => '2026-10-20']);
        $log = $this->createDailyLogRecord($placement['internship'], [
            'date' => '2026-10-20',
            'status' => DailyLogStatus::Approved,
        ]);
        $log->approvals()->create([
            'reviewer_id' => $placement['supervisorUser']->id,
            'action' => 'approved',
            'comments' => 'Approved.',
        ]);
        $this->createAttendanceRecord($placement['internship'], ['date' => '2026-10-21']);

        $assessment = app(RiskService::class)->assess($placement['internship']);

        $this->assertSame(RiskLevel::Normal, $assessment->level);
        $this->assertSame([], $assessment->reasons);
    }

    public function test_a_missing_log_for_a_previous_working_day_is_still_reported(): void
    {
        $placement = $this->createPlacement(internshipAttributes: [
            'start_date' => '2026-10-20',
            'end_date' => '2026-10-21',
        ]);

        $this->createAttendanceRecord($placement['internship'], ['date' => '2026-10-20']);
        $this->createAttendanceRecord($placement['internship'], ['date' => '2026-10-21']);
        $log = $this->createDailyLogRecord($placement['internship'], [
            'date' => '2026-10-21',
            'status' => DailyLogStatus::Approved,
        ]);
        $log->approvals()->create([
            'reviewer_id' => $placement['supervisorUser']->id,
            'action' => 'approved',
            'comments' => 'Approved.',
        ]);

        $assessment = app(RiskService::class)->assess($placement['internship']);

        $this->assertSame(RiskLevel::Warning, $assessment->level);
        $this->assertContains('One attended day has no daily log', $assessment->reasons);
    }
}
