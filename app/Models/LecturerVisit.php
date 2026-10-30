<?php

namespace App\Models;

use App\Enums\VisitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LecturerVisit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'internship_id',
        'lecturer_id',
        'visit_date',
        'purpose',
        'status',
        'observations',
        'student_performance',
        'company_feedback',
        'recommendations',
        'action_items',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'status' => VisitStatus::class,
            'completed_at' => 'datetime',
        ];
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function evidenceFiles(): MorphMany
    {
        return $this->morphMany(EvidenceFile::class, 'attachable');
    }
}
