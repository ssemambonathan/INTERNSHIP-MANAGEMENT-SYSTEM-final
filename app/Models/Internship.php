<?php

namespace App\Models;

use App\Enums\InternshipStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Internship extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'company_id',
        'company_supervisor_id',
        'lecturer_id',
        'internship_period_id',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => InternshipStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisor_id');
    }

    public function companySupervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisor_id');
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(InternshipPeriod::class, 'internship_period_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function dailyLogs(): HasMany
    {
        return $this->hasMany(DailyLog::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(LecturerVisit::class);
    }

    public function reopenings(): HasMany
    {
        return $this->hasMany(DateReopening::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', InternshipStatus::Active);
    }

    public function isActive(): bool
    {
        return $this->status === InternshipStatus::Active;
    }

    public function isWithinDates(?CarbonInterface $date = null): bool
    {
        $date = $date?->copy() ?? now();

        return $date->betweenIncluded($this->start_date->copy()->startOfDay(), $this->end_date->copy()->endOfDay());
    }

    public function isOperational(?CarbonInterface $date = null): bool
    {
        return $this->isActive()
            && $this->isWithinDates($date)
            && $this->company
            && $this->company->isActive();
    }

    public function isReopenedFor(CarbonInterface $date): bool
    {
        return $this->reopenings()
            ->whereDate('date', $date->toDateString())
            ->exists();
    }
}
