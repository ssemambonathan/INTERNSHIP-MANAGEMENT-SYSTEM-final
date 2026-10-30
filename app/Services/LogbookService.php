<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\DailyLogStatus;
use App\Models\Attendance;
use App\Models\DailyLog;
use App\Models\DateReopening;
use App\Models\EvidenceFile;
use App\Models\Internship;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LogbookService
{
    private const REQUIRED_FIELDS = [
        'activities_performed' => 'activities performed',
        'skills_learned' => 'skills learned',
        'challenges' => 'challenges',
        'solutions' => 'solutions',
        'daily_reflection' => 'daily reflection',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    public function create(Student $student, array $data, bool $submit): DailyLog
    {
        $date = Carbon::parse($data['date'] ?? now()->toDateString())->startOfDay();

        $this->assertNotFuture($date);

        $internship = Internship::query()
            ->with(['period', 'supervisor.user'])
            ->where('student_id', $student->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $date->toDateString())
            ->whereDate('end_date', '>=', $date->toDateString())
            ->latest('start_date')
            ->first();

        if (! $internship) {
            throw ValidationException::withMessages([
                'date' => 'You do not have an active internship covering this date.',
            ]);
        }

        if (! $internship->period->isActive()) {
            throw ValidationException::withMessages([
                'date' => 'The internship period is not open for log submissions.',
            ]);
        }

        $this->assertDateOpen($internship, $date);
        $attendance = $this->qualifyingAttendance($student, $date);

        if ($student->dailyLogs()->whereDate('date', $date->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'date' => 'A daily log already exists for this date. Duplicate submissions are not allowed.',
            ]);
        }

        $log = new DailyLog([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'attendance_id' => $attendance->id,
            'date' => $date->toDateString(),
            'status' => $submit ? DailyLogStatus::Submitted : DailyLogStatus::Draft,
        ]);

        $log->fill($this->payload($data));

        if ($submit) {
            $this->assertComplete($log);
            $log->submitted_at = now();
        }

        $log->save();

        $this->audit->record(
            $submit ? 'log.submitted' : 'log.draft_saved',
            'logbook',
            sprintf('%s %s the daily log for %s.', $student->user->name, $submit ? 'submitted' : 'saved', $date->format('d M Y')),
            $log,
            $student->user,
        );

        if ($submit) {
            $this->notifications->notifyLogSubmitted($log);
        }

        return $log;
    }

    public function update(DailyLog $log, array $data, bool $submit): DailyLog
    {
        if (! $log->isEditableByStudent()) {
            throw ValidationException::withMessages([
                'log' => 'This log can no longer be edited because it has been approved or is awaiting review.',
            ]);
        }

        if ($log->status === DailyLogStatus::Draft) {
            $this->assertDateOpen($log->internship, $log->date->copy());
        }

        $this->qualifyingAttendance($log->student, $log->date->copy());

        $log->fill($this->payload($data));

        if ($submit) {
            $this->assertComplete($log);
            $log->status = DailyLogStatus::Submitted;
            $log->submitted_at = now();
            $log->reviewed_by = null;
            $log->reviewed_at = null;
            $log->rejection_reason = null;
        }

        $log->save();

        $this->audit->record(
            $submit ? 'log.resubmitted' : 'log.updated',
            'logbook',
            sprintf('%s %s the daily log for %s.', $log->student->user->name, $submit ? 'submitted' : 'updated', $log->date->format('d M Y')),
            $log,
            $log->student->user,
        );

        if ($submit) {
            $this->notifications->notifyLogSubmitted($log);
        }

        return $log;
    }

    public function submit(DailyLog $log): DailyLog
    {
        if (! $log->isEditableByStudent()) {
            throw ValidationException::withMessages([
                'log' => 'Only draft or rejected logs can be submitted.',
            ]);
        }

        if ($log->status === DailyLogStatus::Draft) {
            $this->assertDateOpen($log->internship, $log->date->copy());
        }

        $this->qualifyingAttendance($log->student, $log->date->copy());
        $this->assertComplete($log);

        $log->update([
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);

        $this->audit->record(
            'log.submitted',
            'logbook',
            sprintf('%s submitted the daily log for %s.', $log->student->user->name, $log->date->format('d M Y')),
            $log,
            $log->student->user,
        );

        $this->notifications->notifyLogSubmitted($log);

        return $log->refresh();
    }

    public function attachEvidence(DailyLog $log, UploadedFile $file, User $uploader): EvidenceFile
    {
        if (! $log->isEditableByStudent()) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence can only be added while the log is a draft or has been returned for correction.',
            ]);
        }

        if ($log->evidenceFiles()->count() >= 5) {
            throw ValidationException::withMessages([
                'evidence' => 'A maximum of five evidence files may be attached to a log.',
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
        $path = $file->storeAs('evidence/'.$log->id, $storedName, 'local');

        if (! $path) {
            throw ValidationException::withMessages([
                'evidence' => 'The file could not be stored. Please try again.',
            ]);
        }

        $evidence = $log->evidenceFiles()->create([
            'original_name' => Str::limit(basename($file->getClientOriginalName()), 240, ''),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize(),
            'uploaded_by' => $uploader->id,
        ]);

        $this->audit->record(
            'evidence.uploaded',
            'logbook',
            sprintf('%s uploaded evidence "%s" for the log of %s.', $uploader->name, $evidence->original_name, $log->date->format('d M Y')),
            $log,
            $uploader,
        );

        return $evidence;
    }

    public function deleteEvidence(EvidenceFile $evidence): void
    {
        $log = $evidence->attachable;

        if (! $log instanceof DailyLog || ! $log->isEditableByStudent()) {
            throw ValidationException::withMessages([
                'evidence' => 'Evidence cannot be removed from a log that is awaiting review or approved.',
            ]);
        }

        Storage::disk('local')->delete($evidence->path);
        $evidence->delete();

        $this->audit->record(
            'evidence.deleted',
            'logbook',
            sprintf('Evidence "%s" was removed from the log of %s.', $evidence->original_name, $log->date->format('d M Y')),
            $log,
            auth()->user(),
        );
    }

    public function reopenDate(Internship $internship, Carbon $date, User $reviewer, string $reason): DateReopening
    {
        $date = $date->copy()->startOfDay();

        if ($date->isToday() || $date->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'Only past dates need to be reopened. Today is already open for logging.',
            ]);
        }

        if ($date->lt($internship->start_date) || $date->gt($internship->end_date)) {
            throw ValidationException::withMessages([
                'date' => 'The selected date falls outside the internship period.',
            ]);
        }

        $this->qualifyingAttendance($internship->student, $date);

        if ($internship->dailyLogs()->whereDate('date', $date->toDateString())->exists()) {
            throw ValidationException::withMessages([
                'date' => 'A log already exists for that date, so it cannot be reopened.',
            ]);
        }

        if ($internship->isReopenedFor($date)) {
            throw ValidationException::withMessages([
                'date' => 'That date has already been reopened.',
            ]);
        }

        if (blank($reason) || strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' => 'Provide a reason of at least 10 characters for reopening a date.',
            ]);
        }

        $reopening = $internship->reopenings()->create([
            'date' => $date->toDateString(),
            'reopened_by' => $reviewer->id,
            'reason' => $reason,
        ]);

        $this->audit->record(
            'log.date_reopened',
            'logbook',
            sprintf('%s reopened %s for %s. Reason: %s', $reviewer->name, $date->format('d M Y'), $internship->student->user->name, $reason),
            $reopening,
            $reviewer,
        );

        $this->notifications->notifyLogReopened($reopening);

        return $reopening;
    }

    public function startReview(DailyLog $log, User $reviewer): DailyLog
    {
        if ($log->status !== DailyLogStatus::Submitted) {
            throw ValidationException::withMessages(['log' => 'Only submitted logs can be moved into review.']);
        }

        $log->update(['status' => DailyLogStatus::UnderReview]);

        $this->audit->record(
            'log.review_started',
            'approvals',
            sprintf('%s started reviewing the log of %s for %s.', $reviewer->name, $log->student->user->name, $log->date->format('d M Y')),
            $log,
            $reviewer,
        );

        return $log->refresh();
    }

    public function approve(DailyLog $log, User $reviewer, ?string $comment = null): DailyLog
    {
        $this->assertPending($log);

        $log->update([
            'status' => DailyLogStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        $log->approvals()->create([
            'reviewer_id' => $reviewer->id,
            'action' => ApprovalAction::Approved,
            'comments' => $comment,
        ]);

        $this->audit->record(
            'log.approved',
            'approvals',
            sprintf('%s approved the log of %s for %s.', $reviewer->name, $log->student->user->name, $log->date->format('d M Y')),
            $log,
            $reviewer,
        );

        $this->notifications->notifyLogReviewed($log, ApprovalAction::Approved, $comment);

        return $log->refresh();
    }

    public function reject(DailyLog $log, User $reviewer, string $reason, bool $correctionRequested = false): DailyLog
    {
        $this->assertPending($log);

        if (blank($reason) || strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'reason' => 'Provide a reason of at least 10 characters when rejecting or requesting corrections.',
            ]);
        }

        $action = $correctionRequested ? ApprovalAction::CorrectionRequested : ApprovalAction::Rejected;

        $log->update([
            'status' => DailyLogStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $log->approvals()->create([
            'reviewer_id' => $reviewer->id,
            'action' => $action,
            'comments' => $reason,
        ]);

        $this->audit->record(
            'log.rejected',
            'approvals',
            sprintf('%s %s the log of %s for %s. Reason: %s', $reviewer->name, $correctionRequested ? 'requested a correction on' : 'rejected', $log->student->user->name, $log->date->format('d M Y'), $reason),
            $log,
            $reviewer,
        );

        $this->notifications->notifyLogReviewed($log, $action, $reason);

        return $log->refresh();
    }

    public function comment(DailyLog $log, User $reviewer, string $comment): void
    {
        if (blank($comment)) {
            throw ValidationException::withMessages(['comment' => 'A comment cannot be empty.']);
        }

        $log->approvals()->create([
            'reviewer_id' => $reviewer->id,
            'action' => ApprovalAction::Commented,
            'comments' => $comment,
        ]);

        $this->audit->record(
            'log.comment_added',
            'approvals',
            sprintf('%s commented on the log of %s for %s.', $reviewer->name, $log->student->user->name, $log->date->format('d M Y')),
            $log,
            $reviewer,
        );
    }

    private function assertPending(DailyLog $log): void
    {
        if (! $log->status->isPendingReview()) {
            throw ValidationException::withMessages([
                'log' => 'Only submitted or under-review logs can be reviewed.',
            ]);
        }
    }

    private function assertNotFuture(Carbon $date): void
    {
        if ($date->isFuture()) {
            throw ValidationException::withMessages([
                'date' => 'Future dates cannot be submitted.',
            ]);
        }
    }

    private function assertDateOpen(Internship $internship, Carbon $date): void
    {
        $this->assertNotFuture($date);

        if ($date->lt($internship->start_date) || $date->gt($internship->end_date)) {
            throw ValidationException::withMessages([
                'date' => 'The selected date falls outside your internship period.',
            ]);
        }

        if (! $date->isToday() && ! $internship->isReopenedFor($date)) {
            throw ValidationException::withMessages([
                'date' => 'Historical log submission is locked. Ask your lecturer to reopen this date if you genuinely missed it.',
            ]);
        }
    }

    private function qualifyingAttendance(Student $student, Carbon $date): Attendance
    {
        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if (! $attendance) {
            throw ValidationException::withMessages([
                'date' => 'You cannot submit a log for a day without an attendance record.',
            ]);
        }

        if (! $attendance->qualifiesForLog()) {
            throw ValidationException::withMessages([
                'date' => 'Your attendance for this date is '.$attendance->status->label().'. A supervisor must verify it before a log can be submitted.',
            ]);
        }

        return $attendance;
    }

    private function payload(array $data): array
    {
        return [
            'activities_performed' => $this->clean($data['activities_performed'] ?? null),
            'skills_learned' => $this->clean($data['skills_learned'] ?? null),
            'challenges' => $this->clean($data['challenges'] ?? null),
            'solutions' => $this->clean($data['solutions'] ?? null),
            'daily_reflection' => $this->clean($data['daily_reflection'] ?? null),
            'hours_worked' => isset($data['hours_worked']) ? round((float) $data['hours_worked'], 1) : null,
        ];
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function assertComplete(DailyLog $log): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field => $label) {
            if (blank($log->{$field})) {
                $errors[$field] = 'The '.$label.' field is required before submission.';
            }
        }

        if ($log->hours_worked === null || $log->hours_worked < 0.5 || $log->hours_worked > 24) {
            $errors['hours_worked'] = 'Enter the hours worked (between 0.5 and 24 hours).';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
