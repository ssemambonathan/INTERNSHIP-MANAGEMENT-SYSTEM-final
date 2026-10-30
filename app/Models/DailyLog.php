<?php

namespace App\Models;

use App\Enums\DailyLogStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'internship_id',
        'attendance_id',
        'date',
        'activities_performed',
        'skills_learned',
        'challenges',
        'solutions',
        'daily_reflection',
        'hours_worked',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_worked' => 'float',
            'status' => DailyLogStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'subject');
    }

    public function scopePendingReview(Builder $query): Builder
    {
        return $query->whereIn('status', [DailyLogStatus::Submitted->value, DailyLogStatus::UnderReview->value]);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', DailyLogStatus::Approved);
    }

    public function isEditableByStudent(): bool
    {
        return $this->status->isEditableByStudent();
    }

    public function isComplete(): bool
    {
        return filled($this->activities_performed)
            && filled($this->skills_learned)
            && filled($this->challenges)
            && filled($this->solutions)
            && filled($this->daily_reflection)
            && $this->hours_worked !== null;
    }
}
