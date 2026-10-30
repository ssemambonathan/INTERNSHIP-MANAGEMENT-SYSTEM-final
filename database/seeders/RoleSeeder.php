<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [RoleName::Admin, 'Administrator', 'Full institutional management and oversight.'],
            [RoleName::Student, 'Student', 'Manages internship attendance and daily learning records.'],
            [RoleName::Supervisor, 'Company Supervisor', 'Verifies attendance and reviews daily logs at the company.'],
            [RoleName::Lecturer, 'Lecturer', 'Monitors assigned students and conducts internship visits.'],
        ];

        foreach ($roles as [$name, $label, $description]) {
            Role::updateOrCreate(
                ['name' => $name->value],
                ['label' => $label, 'description' => $description],
            );
        }
    }
}
