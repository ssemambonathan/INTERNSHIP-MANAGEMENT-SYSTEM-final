<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'student_id',
        'internship_id',
        'date',
        'check_in_time',
        'check_out_time',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'distance_from_company',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_distance',
        'worked_minutes',
        'late_minutes',
        'status',
        'verification_method',
        'notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in_time' => 'datetime',
            'check_out_time' => 'datetime',
            'check_in_latitude' => 'float',
            'check_in_longitude' => 'float',
            'check_in_accuracy' => 'float',
            'distance_from_company' => 'float',
            'check_out_latitude' => 'float',
            'check_out_longitude' => 'float',
            'check_out_accuracy' => 'float',
            'check_out_distance' => 'float',
            'worked_minutes' => 'integer',
            'late_minutes' => 'integer',
            'status' => AttendanceStatus::class,
            'verification_method' => VerificationMethod::class,
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'subject');
    }

    public function scopeAttended(Builder $query): Builder
    {
        return $query->whereIn('status', [AttendanceStatus::Present->value, AttendanceStatus::Late->value]);
    }

    public function scopeFlagged(Builder $query): Builder
    {
        return $query->whereIn('status', [AttendanceStatus::OutsideLocation->value, AttendanceStatus::Incomplete->value]);
    }

    public function qualifiesForLog(): bool
    {
        return $this->status->countsAsAttended();
    }

    public function hasCheckedIn(): bool
    {
        return $this->check_in_time !== null;
    }

    public function hasCheckedOut(): bool
    {
        return $this->check_out_time !== null;
    }
}
