<?php

namespace Tests\Support;

use App\Enums\AttendanceStatus;
use App\Enums\CompanyStatus;
use App\Enums\DailyLogStatus;
use App\Enums\InternshipStatus;
use App\Enums\PeriodStatus;
use App\Enums\UserStatus;
use App\Enums\VerificationMethod;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Course;
use App\Models\DailyLog;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Internship;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

trait CreatesInternshipContext
{
    protected Faculty $faculty;

    protected Department $department;

    protected Course $course;

    protected AcademicYear $academicYear;

    protected InternshipPeriod $period;

    protected Company $company;

    protected function setUpInternshipContext(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 08:05:00'));

        $this->seed(RoleSeeder::class);
        $this->seed(SettingSeeder::class);

        $this->faculty = Faculty::create([
            'name' => 'Faculty of Computing',
            'code' => 'FOC',
        ]);

        $this->department = Department::create([
            'faculty_id' => $this->faculty->id,
            'name' => 'Computer Science',
            'code' => 'CS',
        ]);

        $this->course = Course::create([
            'department_id' => $this->department->id,
            'name' => 'BSc Computer Science',
            'code' => 'BCS',
            'degree_level' => 'Bachelor',
            'duration_years' => 3,
        ]);

        $this->academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-08-01',
            'end_date' => '2027-07-31',
            'is_current' => true,
        ]);

        $this->period = InternshipPeriod::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semester Two Internship',
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'status' => PeriodStatus::Active,
        ]);

        $this->company = Company::create([
            'name' => 'TechFlow Systems',
            'industry' => 'Software',
            'address' => 'Plot 10 Innovation Road',
            'city' => 'Kampala',
            'phone' => '+256 700 000 001',
            'email' => 'info@techflow.test',
            'latitude' => 0.3475960,
            'longitude' => 32.5825200,
            'allowed_radius' => 150,
            'status' => CompanyStatus::Active,
        ]);
    }

    /**
     * @return array{user: User, student: Student}
     */
    protected function createStudentUser(array $userAttributes = []): array
    {
        $suffix = Str::lower(Str::random(8));

        $user = User::factory()->student()->create(array_merge([
            'name' => 'Student '.Str::upper($suffix),
            'email' => 'student.'.$suffix.'@test.test',
        ], $userAttributes));

        $student = Student::create([
            'user_id' => $user->id,
            'registration_number' => 'REG-'.Str::upper($suffix),
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'course_id' => $this->course->id,
            'academic_year_id' => $this->academicYear->id,
            'year_of_study' => 3,
        ]);

        return ['user' => $user, 'student' => $student];
    }

    /**
     * @return array{user: User, lecturer: Lecturer}
     */
    protected function createLecturerUser(array $userAttributes = []): array
    {
        $suffix = Str::lower(Str::random(8));

        $user = User::factory()->lecturer()->create(array_merge([
            'name' => 'Lecturer '.Str::upper($suffix),
            'email' => 'lecturer.'.$suffix.'@test.test',
        ], $userAttributes));

        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'department_id' => $this->department->id,
            'staff_number' => 'LEC-'.Str::upper($suffix),
            'position' => 'Lecturer',
        ]);

        return ['user' => $user, 'lecturer' => $lecturer];
    }

    /**
     * @return array{user: User, supervisor: CompanySupervisor}
     */
    protected function createSupervisorUser(?Company $company = null, array $userAttributes = []): array
    {
        $suffix = Str::lower(Str::random(8));

        $user = User::factory()->supervisor()->create(array_merge([
            'name' => 'Supervisor '.Str::upper($suffix),
            'email' => 'supervisor.'.$suffix.'@test.test',
        ], $userAttributes));

        $supervisor = CompanySupervisor::create([
            'user_id' => $user->id,
            'company_id' => ($company ?? $this->company)->id,
            'position' => 'Team Lead',
        ]);

        return ['user' => $user, 'supervisor' => $supervisor];
    }

    /**
     * @return array{studentUser: User, student: Student, supervisorUser: User, supervisor: CompanySupervisor, lecturerUser: User, lecturer: Lecturer, internship: Internship}
     */
    protected function createPlacement(
        ?Student $student = null,
        ?CompanySupervisor $supervisor = null,
        ?Lecturer $lecturer = null,
        array $internshipAttributes = [],
    ): array {
        if (! $student) {
            $student = $this->createStudentUser()['student'];
        }

        if (! $supervisor) {
            $supervisor = $this->createSupervisorUser()['supervisor'];
        }

        if (! $lecturer) {
            $lecturer = $this->createLecturerUser()['lecturer'];
        }

        $internship = Internship::create(array_merge([
            'student_id' => $student->id,
            'company_id' => $supervisor->company_id,
            'company_supervisor_id' => $supervisor->id,
            'lecturer_id' => $lecturer->id,
            'internship_period_id' => $this->period->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-12-31',
            'status' => InternshipStatus::Active,
        ], $internshipAttributes));

        return [
            'studentUser' => $student->user,
            'student' => $student,
            'supervisorUser' => $supervisor->user,
            'supervisor' => $supervisor,
            'lecturerUser' => $lecturer->user,
            'lecturer' => $lecturer,
            'internship' => $internship,
        ];
    }

    protected function createAttendanceRecord(Internship $internship, array $attributes = []): Attendance
    {
        return Attendance::create(array_merge([
            'student_id' => $internship->student_id,
            'internship_id' => $internship->id,
            'date' => today()->toDateString(),
            'check_in_time' => now()->copy()->setTime(8, 0),
            'check_out_time' => null,
            'check_in_latitude' => 0.3475960,
            'check_in_longitude' => 32.5825200,
            'check_in_accuracy' => 12,
            'distance_from_company' => 5.0,
            'late_minutes' => 0,
            'status' => AttendanceStatus::Present,
            'verification_method' => VerificationMethod::Gps,
        ], $attributes));
    }

    protected function createDailyLogRecord(Internship $internship, array $attributes = []): DailyLog
    {
        $attendance = Attendance::query()
            ->where('student_id', $internship->student_id)
            ->whereDate('date', $attributes['date'] ?? today()->toDateString())
            ->first();

        if (! $attendance) {
            $attendance = $this->createAttendanceRecord($internship, [
                'date' => $attributes['date'] ?? today()->toDateString(),
            ]);
        }

        return DailyLog::create(array_merge([
            'student_id' => $internship->student_id,
            'internship_id' => $internship->id,
            'attendance_id' => $attendance->id,
            'date' => today()->toDateString(),
            'activities_performed' => 'Implemented the assigned module and reviewed pull requests.',
            'skills_learned' => 'Learned dependency injection and test-driven development.',
            'challenges' => 'Understanding the legacy authentication flow.',
            'solutions' => 'Mapped the flow and wrote integration tests.',
            'daily_reflection' => 'The day improved my confidence in debugging production code.',
            'hours_worked' => 8,
            'status' => DailyLogStatus::Draft,
        ], $attributes));
    }

    protected function createAdminUser(array $userAttributes = []): User
    {
        return User::factory()->admin()->create(array_merge([
            'name' => 'System Administrator',
            'email' => 'admin.'.Str::lower(Str::random(6)).'@test.test',
            'status' => UserStatus::Active,
        ], $userAttributes));
    }
}
