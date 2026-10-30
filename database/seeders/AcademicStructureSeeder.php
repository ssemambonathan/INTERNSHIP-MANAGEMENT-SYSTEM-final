<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\InternshipPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            ['code' => 'FCT', 'name' => 'Faculty of Computing and Technology', 'description' => 'Computing, software engineering, and information technology.'],
            ['code' => 'FBM', 'name' => 'Faculty of Business and Management', 'description' => 'Business, accounting, and management programmes.'],
            ['code' => 'FEN', 'name' => 'Faculty of Engineering', 'description' => 'Electrical, civil, and applied engineering programmes.'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::updateOrCreate(['code' => $faculty['code']], $faculty);
        }

        $departments = [
            ['code' => 'CS', 'name' => 'Department of Computer Science', 'faculty' => 'FCT'],
            ['code' => 'IT', 'name' => 'Department of Information Technology', 'faculty' => 'FCT'],
            ['code' => 'ACC', 'name' => 'Department of Accounting', 'faculty' => 'FBM'],
            ['code' => 'BUS', 'name' => 'Department of Business Administration', 'faculty' => 'FBM'],
            ['code' => 'EEE', 'name' => 'Department of Electrical Engineering', 'faculty' => 'FEN'],
            ['code' => 'CIV', 'name' => 'Department of Civil Engineering', 'faculty' => 'FEN'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'faculty_id' => Faculty::where('code', $department['faculty'])->value('id'),
                ],
            );
        }

        $courses = [
            ['code' => 'BSC-CS', 'name' => 'BSc Computer Science', 'department' => 'CS', 'degree_level' => 'Bachelor', 'duration_years' => 3],
            ['code' => 'BSE', 'name' => 'BSc Software Engineering', 'department' => 'CS', 'degree_level' => 'Bachelor', 'duration_years' => 3],
            ['code' => 'BIT', 'name' => 'BSc Information Technology', 'department' => 'IT', 'degree_level' => 'Bachelor', 'duration_years' => 3],
            ['code' => 'BCOM', 'name' => 'Bachelor of Commerce', 'department' => 'ACC', 'degree_level' => 'Bachelor', 'duration_years' => 3],
            ['code' => 'BBA', 'name' => 'Bachelor of Business Administration', 'department' => 'BUS', 'degree_level' => 'Bachelor', 'duration_years' => 3],
            ['code' => 'BEE', 'name' => 'BSc Electrical Engineering', 'department' => 'EEE', 'degree_level' => 'Bachelor', 'duration_years' => 4],
            ['code' => 'BCE', 'name' => 'BSc Civil Engineering', 'department' => 'CIV', 'degree_level' => 'Bachelor', 'duration_years' => 4],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']],
                [
                    'name' => $course['name'],
                    'department_id' => Department::where('code', $course['department'])->value('id'),
                    'degree_level' => $course['degree_level'],
                    'duration_years' => $course['duration_years'],
                ],
            );
        }

        $now = now();
        $startYear = $now->month >= 8 ? $now->year : $now->year - 1;

        $academicYear = AcademicYear::updateOrCreate(
            ['name' => $startYear.'/'.($startYear + 1)],
            [
                'start_date' => Carbon::create($startYear, 8, 1)->toDateString(),
                'end_date' => Carbon::create($startYear + 1, 6, 30)->toDateString(),
                'is_current' => true,
            ],
        );

        InternshipPeriod::updateOrCreate(
            ['name' => $startYear.' Semester II Internship'],
            [
                'academic_year_id' => $academicYear->id,
                'start_date' => $now->copy()->subDays(45)->toDateString(),
                'end_date' => $now->copy()->addDays(60)->toDateString(),
                'status' => 'active',
            ],
        );

        InternshipPeriod::updateOrCreate(
            ['name' => $startYear.' Semester I Internship'],
            [
                'academic_year_id' => $academicYear->id,
                'start_date' => $now->copy()->subDays(180)->toDateString(),
                'end_date' => $now->copy()->subDays(75)->toDateString(),
                'status' => 'closed',
            ],
        );
    }
}
