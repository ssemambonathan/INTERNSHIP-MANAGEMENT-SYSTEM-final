<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Student = 'student';
    case Supervisor = 'supervisor';
    case Lecturer = 'lecturer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Student => 'Student',
            self::Supervisor => 'Company Supervisor',
            self::Lecturer => 'Lecturer',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin => 'admin.dashboard',
            self::Student => 'student.dashboard',
            self::Supervisor => 'supervisor.dashboard',
            self::Lecturer => 'lecturer.dashboard',
        };
    }
}
