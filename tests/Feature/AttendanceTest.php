<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\PeriodStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Notifications\AttendanceCorrectedNotification;
use App\Notifications\AttendanceFlaggedNotification;
use App\Notifications\AttendanceReviewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    private const COMPANY_LAT = 0.3475960;

    private const COMPANY_LNG = 32.5825200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_student_can_check_in_inside_the_geofence(): void
    {
        $placement = $this->createPlacement();

        $response = $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
            'accuracy' => 8,
        ]);

        $response->assertRedirect(route('student.attendance.index'));

        $attendance = Attendance::firstOrFail();

        $this->assertSame(AttendanceStatus::Present, $attendance->status);
        $this->assertSame(0.0, (float) $attendance->distance_from_company);
        $this->assertSame(today()->toDateString(), $attendance->date->toDateString());
        $this->assertNotNull($attendance->check_in_time);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.check_in']);
    }

    public function test_check_in_after_the_late_threshold_is_marked_late(): void
    {
        $this->travelTo(now()->setTime(9, 15));

        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertRedirect();

        $attendance = Attendance::firstOrFail();

        $this->assertSame(AttendanceStatus::Late, $attendance->status);
        $this->assertSame(45, $attendance->late_minutes);
    }

    public function test_check_in_outside_the_geofence_is_flagged_and_supervisor_is_notified(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => 0.3525960,
            'longitude' => self::COMPANY_LNG,
            'accuracy' => 15,
        ])->assertRedirect();

        $attendance = Attendance::firstOrFail();

        $this->assertSame(AttendanceStatus::OutsideLocation, $attendance->status);
        $this->assertGreaterThan(150, $attendance->distance_from_company);
        $this->assertDatabaseHas('notifications', [
            'type' => AttendanceFlaggedNotification::class,
            'notifiable_id' => $placement['supervisorUser']->id,
        ]);
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        $placement = $this->createPlacement();
        $this->createAttendanceRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertSessionHasErrors('attendance');

        $this->assertSame(1, Attendance::count());
    }

    public function test_check_in_is_blocked_when_the_period_is_closed(): void
    {
        $placement = $this->createPlacement();
        $this->period->update(['status' => PeriodStatus::Closed]);

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertSessionHasErrors('attendance');

        $this->assertSame(0, Attendance::count());
    }

    public function test_student_without_a_placement_cannot_check_in(): void
    {
        $student = $this->createStudentUser()['user'];

        $this->actingAs($student)->post(route('student.attendance.check-in'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertSessionHasErrors('attendance');

        $this->assertSame(0, Attendance::count());
    }

    public function test_coordinates_outside_valid_ranges_are_rejected(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-in'), [
            'latitude' => 120,
            'longitude' => 400,
        ])->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertSame(0, Attendance::count());
    }

    public function test_short_working_day_is_marked_incomplete_on_check_out(): void
    {
        $placement = $this->createPlacement();

        $this->createAttendanceRecord($placement['internship'], [
            'check_in_time' => now()->copy()->subMinutes(120),
        ]);

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-out'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertRedirect();

        $attendance = Attendance::firstOrFail();

        $this->assertSame(AttendanceStatus::Incomplete, $attendance->status);
        $this->assertSame(120, $attendance->worked_minutes);
        $this->assertNotNull($attendance->check_out_time);
        $this->assertNotNull($attendance->check_out_distance);
    }

    public function test_duplicate_check_out_is_rejected(): void
    {
        $placement = $this->createPlacement();

        $this->createAttendanceRecord($placement['internship'], [
            'check_in_time' => now()->copy()->subHours(8),
            'check_out_time' => now()->copy()->subMinutes(10),
            'worked_minutes' => 470,
        ]);

        $this->actingAs($placement['studentUser'])->post(route('student.attendance.check-out'), [
            'latitude' => self::COMPANY_LAT,
            'longitude' => self::COMPANY_LNG,
        ])->assertSessionHasErrors('attendance');
    }

    public function test_supervisor_can_verify_flagged_attendance(): void
    {
        $placement = $this->createPlacement();

        $attendance = $this->createAttendanceRecord($placement['internship'], [
            'status' => AttendanceStatus::OutsideLocation,
            'distance_from_company' => 420,
            'notes' => 'Check-in was outside the company geofence.',
        ]);

        $this->actingAs($placement['supervisorUser'])
            ->post(route('supervisor.attendance.verify', $attendance))
            ->assertRedirect();

        $attendance->refresh();

        $this->assertSame(AttendanceStatus::Present, $attendance->status);
        $this->assertSame(VerificationMethod::Manual, $attendance->verification_method);
        $this->assertSame($placement['supervisorUser']->id, $attendance->reviewed_by);
        $this->assertDatabaseHas('approvals', ['action' => 'verified']);
        $this->assertDatabaseHas('notifications', [
            'type' => AttendanceReviewedNotification::class,
            'notifiable_id' => $placement['studentUser']->id,
        ]);
    }

    public function test_supervisor_rejection_requires_a_reason(): void
    {
        $placement = $this->createPlacement();
        $attendance = $this->createAttendanceRecord($placement['internship'], [
            'status' => AttendanceStatus::Incomplete,
        ]);

        $this->actingAs($placement['supervisorUser'])
            ->post(route('supervisor.attendance.reject', $attendance), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->actingAs($placement['supervisorUser'])
            ->post(route('supervisor.attendance.reject', $attendance), ['reason' => 'Left early without approval.'])
            ->assertRedirect();

        $this->assertSame(AttendanceStatus::Rejected, $attendance->fresh()->status);
    }

    public function test_an_unrelated_supervisor_cannot_review_attendance(): void
    {
        $placement = $this->createPlacement();
        $unrelated = $this->createPlacement();
        $attendance = $this->createAttendanceRecord($placement['internship'], [
            'status' => AttendanceStatus::OutsideLocation,
        ]);

        $this->actingAs($unrelated['supervisorUser'])
            ->post(route('supervisor.attendance.verify', $attendance))
            ->assertForbidden();

        $this->assertSame(AttendanceStatus::OutsideLocation, $attendance->fresh()->status);
    }

    public function test_admin_correction_updates_the_record_notifies_the_student_and_is_audited(): void
    {
        $placement = $this->createPlacement();
        $admin = $this->createAdminUser();
        $attendance = $this->createAttendanceRecord($placement['internship'], [
            'check_in_time' => now()->copy()->setTime(9, 10),
            'status' => AttendanceStatus::OutsideLocation,
            'distance_from_company' => 500,
        ]);

        $this->actingAs($admin)->post(route('admin.attendance.correct', $attendance), [
            'check_in_time' => '08:00',
            'check_out_time' => '16:30',
            'status' => AttendanceStatus::Present->value,
            'reason' => 'Verified with the company that the student was on site.',
        ])->assertRedirect();

        $attendance->refresh();

        $this->assertSame('08:00', $attendance->check_in_time->format('H:i'));
        $this->assertSame('16:30', $attendance->check_out_time->format('H:i'));
        $this->assertSame(AttendanceStatus::Present, $attendance->status);
        $this->assertSame(510, $attendance->worked_minutes);
        $this->assertSame(VerificationMethod::Manual, $attendance->verification_method);
        $this->assertStringContainsString('Verified with the company', $attendance->notes);

        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.corrected']);
        $this->assertDatabaseHas('notifications', [
            'type' => AttendanceCorrectedNotification::class,
            'notifiable_id' => $placement['studentUser']->id,
        ]);
    }

    public function test_admin_correction_requires_a_reason_and_valid_times(): void
    {
        $placement = $this->createPlacement();
        $admin = $this->createAdminUser();
        $attendance = $this->createAttendanceRecord($placement['internship']);

        $this->actingAs($admin)->post(route('admin.attendance.correct', $attendance), [
            'check_in_time' => '17:00',
            'check_out_time' => '08:00',
            'status' => AttendanceStatus::Present->value,
            'reason' => 'Correcting the recorded times after checking the site register.',
        ])->assertSessionHasErrors('check_out_time');

        $this->actingAs($admin)->post(route('admin.attendance.correct', $attendance), [
            'check_in_time' => '08:00',
            'check_out_time' => '16:30',
            'status' => AttendanceStatus::Present->value,
            'reason' => 'short',
        ])->assertSessionHasErrors('reason');

        $this->assertNotSame('17:00', $attendance->fresh()->check_in_time->format('H:i'));
    }
}
