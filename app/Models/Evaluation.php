<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    protected $fillable = [
        'internship_id',
        'company_supervisor_id',
        'week_start',
        'week_end',
        'technical_skills',
        'communication',
        'professionalism',
        'teamwork',
        'punctuality',
        'initiative',
        'strengths',
        'areas_for_improvement',
        'remarks',
        'recommendation',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'technical_skills' => 'integer',
            'communication' => 'integer',
            'professionalism' => 'integer',
            'teamwork' => 'integer',
            'punctuality' => 'integer',
            'initiative' => 'integer',
        ];
    }

    public static array $criteria = [
        'technical_skills' => 'Technical Skills',
        'communication' => 'Communication',
        'professionalism' => 'Professionalism',
        'teamwork' => 'Teamwork',
        'punctuality' => 'Punctuality',
        'initiative' => 'Initiative',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(CompanySupervisor::class, 'company_supervisor_id');
    }

    public function averageScore(): float
    {
        $scores = array_map(fn (string $field) => $this->{$field}, array_keys(self::$criteria));

        return round(array_sum($scores) / count($scores), 2);
    }
}
