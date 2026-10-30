<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'industry',
        'address',
        'city',
        'phone',
        'email',
        'latitude',
        'longitude',
        'allowed_radius',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'allowed_radius' => 'integer',
            'status' => CompanyStatus::class,
        ];
    }

    public function supervisors(): HasMany
    {
        return $this->hasMany(CompanySupervisor::class);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }
}
