<?php

namespace App\Services;

use App\Models\EvidenceFile;
use App\Models\Internship;
use App\Models\LecturerVisit;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisitService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(User $actor, array $data): LecturerVisit
    {
        $internship = Internship::query()->findOrFail($data['internship_id']);

        $this->assertLecturer($internship, $actor);

        $visit = LecturerVisit::create([
            'internship_id' => $internship->id,
            'lecturer_id' => $actor->lecturer->id,
            'visit_date' => $data['visit_date'],
            'purpose' => $data['purpose'],
            'status' => 'scheduled',
        ]);

        $this->audit->record(
            'visit.created',
            'visits',
            sprintf('%s scheduled a supervision visit for %s on %s.', $actor->name, $internship->student->user->name, $visit->visit_date->format('d M Y')),
            $visit,
            $actor,
        );

        app(NotificationService::class)->notifyVisitScheduled($visit);

        return $visit;
    }

    public function update(LecturerVisit $visit, User $actor, array $data): LecturerVisit
    {
        $this->assertOwner($visit, $actor);

        if ($visit->status->value !== 'scheduled') {
            throw ValidationException::withMessages(['visit' => 'Only scheduled visits can be edited.']);
        }

        $visit->update([
            'visit_date' => $data['visit_date'],
            'purpose' => $data['purpose'],
        ]);

        $this->audit->record(
            'visit.updated',
            'visits',
            sprintf('%s updated the supervision visit for %s on %s.', $actor->name, $visit->internship->student->user->name, $visit->visit_date->format('d M Y')),
            $visit,
            $actor,
        );

        app(NotificationService::class)->notifyVisitScheduled($visit);

        return $visit->refresh();
    }

    public function complete(LecturerVisit $visit, User $actor, array $data): LecturerVisit
    {
        $this->assertOwner($visit, $actor);

        if ($visit->status->value !== 'scheduled') {
            throw ValidationException::withMessages(['visit' => 'Only a scheduled visit can be completed.']);
        }

        $visit->update([
            'status' => 'completed',
            'completed_at' => now(),
            'observations' => $data['observations'],
            'student_performance' => $data['student_performance'] ?? null,
            'company_feedback' => $data['company_feedback'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
            'action_items' => $data['action_items'] ?? null,
        ]);

        $this->audit->record(
            'visit.completed',
            'visits',
            sprintf('%s recorded the supervision visit outcome for %s.', $actor->name, $visit->internship->student->user->name),
            $visit,
            $actor,
        );

        return $visit->refresh();
    }

    public function cancel(LecturerVisit $visit, User $actor): LecturerVisit
    {
        $this->assertOwner($visit, $actor);

        if ($visit->status->value !== 'scheduled') {
            throw ValidationException::withMessages(['visit' => 'Only a scheduled visit can be cancelled.']);
        }

        $visit->update(['status' => 'cancelled']);

        $this->audit->record(
            'visit.cancelled',
            'visits',
            sprintf('%s cancelled the supervision visit for %s on %s.', $actor->name, $visit->internship->student->user->name, $visit->visit_date->format('d M Y')),
            $visit,
            $actor,
        );

        return $visit->refresh();
    }

    public function attachEvidence(LecturerVisit $visit, UploadedFile $file, User $uploader): EvidenceFile
    {
        $this->assertOwner($visit, $uploader);

        if ($visit->evidenceFiles()->count() >= 5) {
            throw ValidationException::withMessages([
                'evidence' => 'A maximum of five evidence files may be attached to a visit.',
            ]);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence must be a JPG, JPEG, PNG, PDF, or DOCX file.',
            ]);
        }

        $storedName = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs('evidence/visits/'.$visit->id, $storedName, 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'evidence' => 'The file could not be stored. Please try again.',
            ]);
        }

        $evidence = $visit->evidenceFiles()->create([
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 240, ''),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => $uploader->id,
        ]);

        $this->audit->record(
            'visit.evidence_uploaded',
            'visits',
            sprintf('%s uploaded visit evidence "%s".', $uploader->name, $evidence->original_name),
            $visit,
            $uploader,
        );

        return $evidence;
    }

    public function deleteEvidence(EvidenceFile $evidence, User $actor): void
    {
        $visit = $evidence->attachable;

        if (! $visit instanceof LecturerVisit) {
            throw ValidationException::withMessages(['evidence' => 'This file is not attached to a visit.']);
        }

        $this->assertOwner($visit, $actor);

        if ($evidence->uploaded_by !== $actor->id) {
            throw ValidationException::withMessages(['evidence' => 'You can only remove your own evidence files.']);
        }

        Storage::disk('local')->delete($evidence->path);
        $evidence->delete();

        $this->audit->record(
            'visit.evidence_deleted',
            'visits',
            sprintf('%s removed visit evidence "%s".', $actor->name, $evidence->original_name),
            $visit,
            $actor,
        );
    }

    private function assertLecturer(Internship $internship, User $actor): void
    {
        if (! $actor->isLecturer() || ! $actor->lecturer || $internship->lecturer_id !== $actor->lecturer->id) {
            throw ValidationException::withMessages([
                'internship_id' => 'You can only schedule visits for students assigned to you.',
            ]);
        }
    }

    private function assertOwner(LecturerVisit $visit, User $actor): void
    {
        if (! $actor->isLecturer() || ! $actor->lecturer || $visit->lecturer_id !== $actor->lecturer->id) {
            throw ValidationException::withMessages([
                'visit' => 'You are not authorised to modify this visit.',
            ]);
        }
    }
}
