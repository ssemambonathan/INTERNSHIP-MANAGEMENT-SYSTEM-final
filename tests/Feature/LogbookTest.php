<?php

namespace Tests\Feature;

use App\Enums\DailyLogStatus;
use App\Models\DailyLog;
use App\Models\EvidenceFile;
use App\Notifications\LogReviewedNotification;
use App\Notifications\LogSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_student_can_save_a_draft_for_a_day_with_attendance(): void
    {
        $placement = $this->createPlacement();
        $this->createAttendanceRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => today()->toDateString(),
            'action' => 'draft',
            'activities_performed' => 'Started onboarding with the engineering team.',
        ])->assertRedirect();

        $log = DailyLog::firstOrFail();

        $this->assertSame(DailyLogStatus::Draft, $log->status);
        $this->assertNotNull($log->attendance_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'log.draft_saved']);
    }

    public function test_a_draft_cannot_be_submitted_without_required_fields(): void
    {
        $placement = $this->createPlacement();
        $this->createAttendanceRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => today()->toDateString(),
            'action' => 'submit',
            'activities_performed' => 'Short',
        ])->assertSessionHasErrors([
            'activities_performed',
            'skills_learned',
            'challenges',
            'solutions',
            'daily_reflection',
            'hours_worked',
        ]);

        $this->assertSame(0, DailyLog::count());
    }

    public function test_complete_log_is_submitted_and_supervisor_notified(): void
    {
        $placement = $this->createPlacement();
        $this->createAttendanceRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => today()->toDateString(),
            'action' => 'submit',
            'activities_performed' => 'Built the attendance export endpoint and wrote tests.',
            'skills_learned' => 'Practised Laravel policies and database transactions.',
            'challenges' => 'The legacy report query was slow.',
            'solutions' => 'Added indexes and eager loading.',
            'daily_reflection' => 'I learned how to verify a performance fix with measurements.',
            'hours_worked' => 8,
        ])->assertRedirect();

        $log = DailyLog::firstOrFail();

        $this->assertSame(DailyLogStatus::Submitted, $log->status);
        $this->assertNotNull($log->submitted_at);
        $this->assertDatabaseHas('notifications', [
            'type' => LogSubmittedNotification::class,
            'notifiable_id' => $placement['supervisorUser']->id,
        ]);
    }

    public function test_duplicate_log_for_the_same_day_is_rejected(): void
    {
        $placement = $this->createPlacement();
        $this->createDailyLogRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => today()->toDateString(),
            'action' => 'draft',
        ])->assertSessionHasErrors('date');

        $this->assertSame(1, DailyLog::count());
    }

    public function test_future_dated_logs_are_rejected(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => now()->addDay()->toDateString(),
            'action' => 'draft',
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, DailyLog::count());
    }

    public function test_a_log_cannot_be_created_without_qualifying_attendance(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->post(route('student.logs.store'), [
            'date' => today()->toDateString(),
            'action' => 'draft',
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, DailyLog::count());
    }

    public function test_historical_dates_are_locked_until_reopened(): void
    {
        $placement = $this->createPlacement();
        $yesterday = today()->copy()->subDay();

        $this->createAttendanceRecord($placement['internship'], [
            'date' => $yesterday->toDateString(),
            'check_in_time' => $yesterday->copy()->setTime(8, 0),
        ]);

        $payload = [
            'date' => $yesterday->toDateString(),
            'action' => 'draft',
        ];

        $this->actingAs($placement['studentUser'])
            ->post(route('student.logs.store'), $payload)
            ->assertSessionHasErrors('date');

        $this->actingAs($placement['lecturerUser'])->post(
            route('lecturer.students.reopen', $placement['internship']),
            [
                'date' => $yesterday->toDateString(),
                'reason' => 'Student was on approved field duty and could not submit.',
            ],
        )->assertRedirect(route('lecturer.students.show', $placement['student']->id));

        $this->actingAs($placement['studentUser'])
            ->post(route('student.logs.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('daily_logs', [
            'internship_id' => $placement['internship']->id,
            'date' => $yesterday->toDateString(),
        ]);
    }

    public function test_supervisor_can_comment_and_approve_a_submitted_log(): void
    {
        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.approvals.comment', $log), [
            'comment' => 'Clear and well-documented entry.',
        ])->assertRedirect();

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.approvals.approve', $log), [
            'comment' => 'Approved. Keep up the good work.',
        ])->assertRedirect();

        $log->refresh();

        $this->assertSame(DailyLogStatus::Approved, $log->status);
        $this->assertNotNull($log->reviewed_at);
        $this->assertDatabaseHas('approvals', ['action' => 'approved']);
        $this->assertDatabaseHas('approvals', ['action' => 'commented']);
        $this->assertDatabaseHas('notifications', [
            'type' => LogReviewedNotification::class,
            'notifiable_id' => $placement['studentUser']->id,
        ]);
    }

    public function test_rejection_requires_a_reason_and_student_can_resubmit(): void
    {
        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.approvals.reject', $log), [
            'reason' => 'Too short',
            'decision' => 'correction',
        ])->assertSessionHasErrors('reason');

        $this->actingAs($placement['supervisorUser'])->post(route('supervisor.approvals.reject', $log), [
            'reason' => 'Please describe the deployment steps in more detail.',
            'decision' => 'correction',
        ])->assertRedirect();

        $log->refresh();
        $this->assertSame(DailyLogStatus::Rejected, $log->status);
        $this->assertNotNull($log->rejection_reason);

        $this->actingAs($placement['studentUser'])->put(route('student.logs.update', $log), [
            'date' => today()->toDateString(),
            'action' => 'submit',
            'activities_performed' => 'Documented the deployment steps and rollback procedure.',
            'skills_learned' => 'Learned how release checklists reduce deployment risk.',
            'challenges' => 'The staging environment differed from production.',
            'solutions' => 'Aligned configuration and documented the differences.',
            'daily_reflection' => 'I now understand why repeatable deployments matter.',
            'hours_worked' => 8,
        ])->assertRedirect(route('student.logs.show', $log));

        $log->refresh();

        $this->assertSame(DailyLogStatus::Submitted, $log->status);
        $this->assertNull($log->rejection_reason);
        $this->assertNull($log->reviewed_by);
    }

    public function test_evidence_upload_stores_a_safe_name_and_accepts_allowed_types(): void
    {
        Storage::fake('local');

        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(
            route('student.logs.evidence.store', $log),
            ['evidence' => UploadedFile::fake()->create('site-photo.pdf', 120, 'application/pdf')],
        )->assertRedirect(route('student.logs.show', $log));

        $evidence = EvidenceFile::firstOrFail();

        $this->assertSame('site-photo.pdf', $evidence->original_name);
        $this->assertStringNotContainsString('site-photo', $evidence->path);
        $this->assertStringStartsWith('evidence/'.$log->id.'/', $evidence->path);
        Storage::disk('local')->assertExists($evidence->path);
    }

    public function test_evidence_upload_rejects_disallowed_extensions(): void
    {
        Storage::fake('local');

        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship']);

        $this->actingAs($placement['studentUser'])->post(
            route('student.logs.evidence.store', $log),
            ['evidence' => UploadedFile::fake()->create('payload.html', 10, 'text/html')],
        )->assertSessionHasErrors('evidence');

        $this->assertSame(0, EvidenceFile::count());
    }

    public function test_student_can_delete_evidence_from_an_editable_log(): void
    {
        Storage::fake('local');

        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship']);

        Storage::disk('local')->put('evidence/'.$log->id.'/proof.pdf', 'bytes');

        $evidence = EvidenceFile::create([
            'attachable_type' => $log->getMorphClass(),
            'attachable_id' => $log->id,
            'original_name' => 'proof.pdf',
            'path' => 'evidence/'.$log->id.'/proof.pdf',
            'mime_type' => 'application/pdf',
            'size' => 5,
            'uploaded_by' => $placement['studentUser']->id,
        ]);

        $this->actingAs($placement['studentUser'])
            ->delete(route('student.logs.evidence.destroy', $evidence))
            ->assertRedirect();

        $this->assertDatabaseMissing('evidence_files', ['id' => $evidence->id]);
        Storage::disk('local')->assertMissing('evidence/'.$log->id.'/proof.pdf');
    }

    public function test_evidence_cannot_be_added_once_a_log_is_awaiting_review(): void
    {
        Storage::fake('local');

        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($placement['studentUser'])->post(
            route('student.logs.evidence.store', $log),
            ['evidence' => UploadedFile::fake()->create('late.pdf', 10, 'application/pdf')],
        )->assertForbidden();
    }
}
