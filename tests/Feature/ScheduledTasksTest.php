<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Models\Attendance;
use App\Notifications\AttendanceReminderNotification;
use App\Notifications\DailyLogReminderNotification;
use App\Notifications\PendingApprovalsReminderNotification;
use App\Notifications\RiskAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class ScheduledTasksTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_attendance_reminder_targets_students_without_a_check_in_and_deduplicates(): void
    {
        $missing = $this->createPlacement();
        $present = $this->createPlacement();
        $this->createAttendanceRecord($present['internship']);

        $this->artisan('interntrack:remind-attendance')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'type' => AttendanceReminderNotification::class,
            'notifiable_id' => $missing['studentUser']->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'type' => AttendanceReminderNotification::class,
            'notifiable_id' => $present['studentUser']->id,
        ]);

        $this->artisan('interntrack:remind-attendance')->assertSuccessful();

        $this->assertSame(1, $missing['studentUser']->notifications()
            ->where('type', AttendanceReminderNotification::class)->count());
    }

    public function test_attendance_reminders_are_skipped_on_non_working_days(): void
    {
        $placement = $this->createPlacement();

        $this->travelTo(now()->next(Carbon::SATURDAY)->setTime(9, 0));

        $this->artisan('interntrack:remind-attendance')
            ->expectsOutputToContain('not a configured working day')
            ->assertSuccessful();

        $this->assertSame(0, $placement['studentUser']->notifications()->count());
    }

    public function test_daily_log_reminder_targets_attended_students_without_a_log(): void
    {
        $needsLog = $this->createPlacement();
        $this->createAttendanceRecord($needsLog['internship']);

        $hasLog = $this->createPlacement();
        $this->createDailyLogRecord($hasLog['internship'], ['status' => DailyLogStatus::Draft]);

        $this->artisan('interntrack:remind-logs')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'type' => DailyLogReminderNotification::class,
            'notifiable_id' => $needsLog['studentUser']->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'type' => DailyLogReminderNotification::class,
            'notifiable_id' => $hasLog['studentUser']->id,
        ]);
    }

    public function test_pending_approval_digest_is_sent_once_per_day(): void
    {
        $placement = $this->createPlacement();
        $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->artisan('interntrack:remind-approvals')->assertSuccessful();

        $this->assertSame(1, $placement['supervisorUser']->notifications()
            ->where('type', PendingApprovalsReminderNotification::class)->count());

        $this->artisan('interntrack:remind-approvals')->assertSuccessful();

        $this->assertSame(1, $placement['supervisorUser']->notifications()
            ->where('type', PendingApprovalsReminderNotification::class)->count());
    }

    public function test_risk_alert_is_sent_to_the_lecturer_and_not_repeated_within_the_deduplication_window(): void
    {
        $placement = $this->createPlacement();

        $this->artisan('interntrack:risk-alerts')->assertSuccessful();

        $notifications = $placement['lecturerUser']->notifications()
            ->where('type', RiskAlertNotification::class)
            ->get();

        $this->assertCount(1, $notifications);
        $this->assertSame($placement['internship']->id, $notifications->first()->data['internship_id']);

        $this->artisan('interntrack:risk-alerts')->assertSuccessful();

        $this->assertSame(1, $placement['lecturerUser']->notifications()
            ->where('type', RiskAlertNotification::class)->count());
    }

    public function test_missing_checkouts_are_finalized_for_previous_days_only(): void
    {
        $placement = $this->createPlacement();
        $yesterday = today()->copy()->subDay();

        $previous = $this->createAttendanceRecord($placement['internship'], [
            'date' => $yesterday->toDateString(),
            'check_in_time' => $yesterday->copy()->setTime(8, 0),
            'status' => AttendanceStatus::Present,
        ]);

        $todayRecord = $this->createAttendanceRecord($placement['internship']);

        $this->artisan('interntrack:finalize-checkouts')->assertSuccessful();

        $this->assertSame(AttendanceStatus::Incomplete, $previous->fresh()->status);
        $this->assertSame(AttendanceStatus::Present, $todayRecord->fresh()->status);

        $this->assertSame(0, Attendance::query()
            ->whereDate('date', '<', today()->toDateString())
            ->whereNull('check_out_time')
            ->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value])
            ->count());
    }

    public function test_notifications_page_is_available_to_every_authenticated_role(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->get(route('notifications.index'))->assertOk();
        $this->actingAs($placement['supervisorUser'])->get(route('notifications.index'))->assertOk();
        $this->actingAs($placement['lecturerUser'])->get(route('notifications.index'))->assertOk();
        $this->actingAs($this->createAdminUser())->get(route('notifications.index'))->assertOk();
    }
}
