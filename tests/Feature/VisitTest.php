<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\EvidenceFile;
use App\Models\LecturerVisit;
use App\Notifications\VisitScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_lecturer_can_schedule_a_visit_for_an_assigned_student(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['lecturerUser'])->post(route('lecturer.visits.store'), [
            'internship_id' => $placement['internship']->id,
            'visit_date' => now()->addDays(3)->toDateString(),
            'purpose' => 'Mid-placement progress review with the company supervisor.',
        ])->assertRedirect();

        $visit = LecturerVisit::firstOrFail();

        $this->assertSame(VisitStatus::Scheduled, $visit->status);
        $this->assertSame($placement['lecturer']->id, $visit->lecturer_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.created']);
        $this->assertDatabaseHas('notifications', [
            'type' => VisitScheduledNotification::class,
            'notifiable_id' => $placement['studentUser']->id,
        ]);
    }

    public function test_visit_date_cannot_be_in_the_past(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['lecturerUser'])->post(route('lecturer.visits.store'), [
            'internship_id' => $placement['internship']->id,
            'visit_date' => now()->subDay()->toDateString(),
            'purpose' => 'Late visit attempt that should be blocked.',
        ])->assertSessionHasErrors('visit_date');

        $this->assertSame(0, LecturerVisit::count());
    }

    public function test_lecturer_cannot_schedule_a_visit_for_another_lecturers_student(): void
    {
        $placement = $this->createPlacement();
        $unrelated = $this->createPlacement();

        $this->actingAs($unrelated['lecturerUser'])->post(route('lecturer.visits.store'), [
            'internship_id' => $placement['internship']->id,
            'visit_date' => now()->addDays(2)->toDateString(),
            'purpose' => 'Unauthorised visit attempt.',
        ])->assertForbidden();

        $this->assertSame(0, LecturerVisit::count());
    }

    public function test_lecturer_can_complete_a_visit_and_upload_evidence(): void
    {
        Storage::fake('local');

        $placement = $this->createPlacement();
        $visit = LecturerVisit::create([
            'internship_id' => $placement['internship']->id,
            'lecturer_id' => $placement['lecturer']->id,
            'visit_date' => today()->toDateString(),
            'purpose' => 'On-site supervision and progress review.',
            'status' => VisitStatus::Scheduled,
        ]);

        $this->actingAs($placement['lecturerUser'])->post(route('lecturer.visits.complete', $visit), [
            'observations' => 'The student is working on the reporting module with the data team.',
            'student_performance' => 'Strong technical progress and good communication.',
            'company_feedback' => 'The supervisor is satisfied with attendance and output.',
            'recommendations' => 'Assign more integration work over the next month.',
            'action_items' => 'Review the student report in two weeks.',
        ])->assertRedirect(route('lecturer.visits.show', $visit));

        $visit->refresh();

        $this->assertSame(VisitStatus::Completed, $visit->status);
        $this->assertNotNull($visit->completed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.completed']);

        $this->actingAs($placement['lecturerUser'])->post(
            route('lecturer.visits.evidence.store', $visit),
            ['evidence' => UploadedFile::fake()->create('signed-visit-form.pdf', 90, 'application/pdf')],
        )->assertRedirect(route('lecturer.visits.show', $visit));

        $evidence = EvidenceFile::firstOrFail();
        $this->assertSame('signed-visit-form.pdf', $evidence->original_name);
        Storage::disk('local')->assertExists($evidence->path);

        $this->actingAs($placement['lecturerUser'])
            ->delete(route('lecturer.visits.evidence.destroy', $evidence))
            ->assertRedirect(route('lecturer.visits.show', $visit));

        $this->assertSame(0, EvidenceFile::count());
    }

    public function test_completed_visit_cannot_be_completed_twice_or_edited(): void
    {
        $placement = $this->createPlacement();
        $visit = LecturerVisit::create([
            'internship_id' => $placement['internship']->id,
            'lecturer_id' => $placement['lecturer']->id,
            'visit_date' => today()->toDateString(),
            'purpose' => 'On-site supervision and progress review.',
            'status' => VisitStatus::Completed,
            'observations' => 'Completed visit observations that already exist.',
            'completed_at' => now(),
        ]);

        $this->actingAs($placement['lecturerUser'])->post(route('lecturer.visits.complete', $visit), [
            'observations' => 'Attempting to complete the visit a second time.',
        ])->assertForbidden();

        $this->actingAs($placement['lecturerUser'])->get(route('lecturer.visits.edit', $visit))
            ->assertForbidden();
    }

    public function test_lecturer_can_cancel_a_scheduled_visit(): void
    {
        $placement = $this->createPlacement();
        $visit = LecturerVisit::create([
            'internship_id' => $placement['internship']->id,
            'lecturer_id' => $placement['lecturer']->id,
            'visit_date' => today()->toDateString(),
            'purpose' => 'On-site supervision and progress review.',
            'status' => VisitStatus::Scheduled,
        ]);

        $this->actingAs($placement['lecturerUser'])
            ->delete(route('lecturer.visits.destroy', $visit))
            ->assertRedirect(route('lecturer.visits.index'));

        $visit->refresh();

        $this->assertSame(VisitStatus::Cancelled, $visit->status);
        $this->assertNull($visit->deleted_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'visit.cancelled']);
    }
}
