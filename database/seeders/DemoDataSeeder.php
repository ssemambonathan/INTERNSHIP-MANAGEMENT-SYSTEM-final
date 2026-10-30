<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\DailyLogStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Enums\VerificationMethod;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\CompanySupervisor;
use App\Models\Course;
use App\Models\DailyLog;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\Internship;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\LecturerVisit;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Notifications\BaseNotification;
use App\Notifications\LogReviewedNotification;
use App\Notifications\LogSubmittedNotification;
use App\Notifications\RiskAlertNotification;
use App\Services\GeolocationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private GeolocationService $geo;

    private array $companies = [
        [
            'name' => 'TechFlow Solutions Uganda',
            'industry' => 'Software Development',
            'address' => 'Plot 5 Kampala Road',
            'city' => 'Kampala',
            'phone' => '+256 414 100 200',
            'email' => 'info@techflow.test',
            'latitude' => 0.3476000,
            'longitude' => 32.5825000,
            'allowed_radius' => 150,
        ],
        [
            'name' => 'Nile Innovations Ltd',
            'industry' => 'IT Services',
            'address' => 'Plot 18 Ntinda Road',
            'city' => 'Kampala',
            'phone' => '+256 414 100 201',
            'email' => 'hello@nileinnovations.test',
            'latitude' => 0.3648000,
            'longitude' => 32.6127000,
            'allowed_radius' => 200,
        ],
        [
            'name' => 'Apex Financial Services',
            'industry' => 'Financial Services',
            'address' => 'Nakasero Hill Road',
            'city' => 'Kampala',
            'phone' => '+256 414 100 202',
            'email' => 'contact@apexfinancial.test',
            'latitude' => 0.3298000,
            'longitude' => 32.5812000,
            'allowed_radius' => 150,
        ],
        [
            'name' => 'GreenHarvest Agritech',
            'industry' => 'Agriculture Technology',
            'address' => 'Kawanda Research Station',
            'city' => 'Wakiso',
            'phone' => '+256 414 100 203',
            'email' => 'info@greenharvest.test',
            'latitude' => 0.4020000,
            'longitude' => 32.5350000,
            'allowed_radius' => 250,
        ],
        [
            'name' => 'Brightline Media Group',
            'industry' => 'Media and Communications',
            'address' => 'Plot 7 Kololo Terrace',
            'city' => 'Kampala',
            'phone' => '+256 414 100 204',
            'email' => 'studio@brightline.test',
            'latitude' => 0.3425000,
            'longitude' => 32.5920000,
            'allowed_radius' => 180,
        ],
    ];

    private array $supervisors = [
        ['name' => 'Sarah Nabirye', 'email' => 'supervisor@test.com', 'phone' => '+256 772 100 301', 'position' => 'Software Delivery Lead'],
        ['name' => 'David Okello', 'email' => 'david.okello@nileinnovations.test', 'phone' => '+256 772 100 302', 'position' => 'Head of Technology'],
        ['name' => 'Grace Atim', 'email' => 'grace.atim@apexfinancial.test', 'phone' => '+256 772 100 303', 'position' => 'Finance Operations Manager'],
        ['name' => 'Peter Ssentongo', 'email' => 'peter.ssentongo@greenharvest.test', 'phone' => '+256 772 100 304', 'position' => 'Agronomy Team Lead'],
        ['name' => 'Mercy Namusoke', 'email' => 'mercy.namusoke@brightline.test', 'phone' => '+256 772 100 305', 'position' => 'Production Manager'],
    ];

    private array $lecturers = [
        ['name' => 'Dr. Samuel Kigongo', 'email' => 'lecturer@test.com', 'phone' => '+256 772 200 401', 'staff_number' => 'LEC-001', 'position' => 'Senior Lecturer', 'department' => 'CS'],
        ['name' => 'Dr. Harriet Nansubuga', 'email' => 'harriet.nansubuga@university.test', 'phone' => '+256 772 200 402', 'staff_number' => 'LEC-002', 'position' => 'Lecturer', 'department' => 'IT'],
        ['name' => 'Mr. Joseph Byaruhanga', 'email' => 'joseph.byaruhanga@university.test', 'phone' => '+256 772 200 403', 'staff_number' => 'LEC-003', 'position' => 'Lecturer', 'department' => 'BUS'],
        ['name' => 'Ms. Rebecca Akello', 'email' => 'rebecca.akello@university.test', 'phone' => '+256 772 200 404', 'staff_number' => 'LEC-004', 'position' => 'Lecturer', 'department' => 'EEE'],
    ];

    private array $students = [
        ['Daniel Mugisha', 'student@test.com', '2023/CS/001', 'BSC-CS', 0],
        ['Cynthia Nakato', 'cynthia.nakato@student.test', '2023/CS/014', 'BSC-CS', 0],
        ['Emmanuel Ochieng', 'emmanuel.ochieng@student.test', '2023/SE/006', 'BSE', 0],
        ['Esther Nakamya', 'esther.nakamya@student.test', '2023/IT/003', 'BIT', 0],
        ['Ivan Ssembatya', 'ivan.ssembatya@student.test', '2023/IT/011', 'BIT', 1],
        ['Faith Ayo', 'faith.ayo@student.test', '2023/CS/022', 'BSC-CS', 1],
        ['Moses Kato', 'moses.kato@student.test', '2023/SE/009', 'BSE', 1],
        ['Lydia Nankya', 'lydia.nankya@student.test', '2023/IT/017', 'BIT', 1],
        ['Brian Waiswa', 'brian.waiswa@student.test', '2023/BCOM/004', 'BCOM', 2],
        ['Joan Amuge', 'joan.amuge@student.test', '2023/BBA/013', 'BBA', 2],
        ['Ronald Mubiru', 'ronald.mubiru@student.test', '2023/BCOM/018', 'BCOM', 2],
        ['Sarah Nampijja', 'sarah.nampijja@student.test', '2023/BBA/007', 'BBA', 2],
        ['Alex Twebaze', 'alex.twebaze@student.test', '2023/EEE/005', 'BEE', 3],
        ['Naomi Atieno', 'naomi.atieno@student.test', '2023/CIV/012', 'BCE', 3],
        ['George Kizza', 'george.kizza@student.test', '2023/EEE/020', 'BEE', 3],
        ['Diana Nabaweesi', 'diana.nabaweesi@student.test', '2023/BBA/015', 'BBA', 3],
        ['Timothy Okiror', 'timothy.okiror@student.test', '2023/CS/008', 'BSC-CS', 4],
        ['Lucky Nalwoga', 'lucky.nalwoga@student.test', '2023/IT/019', 'BIT', 4],
        ['Peter Ainebyoona', 'peter.ainebyoona@student.test', '2023/EEE/002', 'BEE', 4],
        ['Brenda Nakimuli', 'brenda.nakimuli@student.test', '2023/BCOM/010', 'BCOM', 4],
    ];

    private array $activities = [
        'Reviewed and updated the project backlog, documented requirements, and joined the daily stand-up with the team.',
        'Prepared and analysed daily operational reports, then discussed the findings with my supervisor.',
        'Supported the team with data entry, quality checks, and reconciliation of daily records.',
        'Observed client meetings and documented action points for follow-up by the relevant officers.',
        'Worked on assigned tasks in the department workflow and documented the steps followed.',
        'Helped prepare materials for an internal review and organised supporting documentation.',
        'Assisted with testing a new process and recorded observations for the team lead.',
        'Completed scheduled field and office tasks with guidance from my workplace mentor.',
    ];

    private array $skills = [
        'Improved my understanding of professional reporting, time management, and workplace communication.',
        'Learned how to use the organisation workflow tools and how to escalate issues appropriately.',
        'Practised data cleaning, accuracy checks, and clear documentation of work completed.',
        'Developed confidence in contributing during team meetings and asking precise questions.',
        'Learned how quality control is applied before work is submitted to clients.',
        'Strengthened my teamwork and problem-solving skills while handling daily assignments.',
    ];

    private array $challenges = [
        'Some tasks were new to me and required careful reading of the standard operating procedures.',
        'Balancing several small assignments required better planning than I initially expected.',
        'I needed more context before I could complete one of the assigned reports.',
        'The volume of data to verify was higher than usual, which slowed the first attempt.',
        'Communication across two teams took longer, so I had to follow up patiently.',
    ];

    private array $solutions = [
        'I asked my supervisor for clarification, prepared a checklist, and completed the work in stages.',
        'I used a simple task list each morning and reviewed progress before the end of the day.',
        'I studied past reports from the department to understand the expected structure.',
        'I double-checked entries against the source documents and corrected discrepancies early.',
        'I set up a short daily check-in with the team lead until the workflow became familiar.',
    ];

    private array $reflections = [
        'I am becoming more comfortable working independently, and I now understand the value of documenting decisions.',
        'Today reinforced the importance of accuracy and clear communication in a professional environment.',
        'I noticed improvement in how I plan my work and how I ask for feedback.',
        'I am learning to connect classroom knowledge with practical workplace expectations.',
        'The day was demanding but gave me a clearer picture of how a real project moves forward.',
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoDataSeeder skipped: demo accounts are not created in production.');

            return;
        }

        if (Internship::withTrashed()->exists()) {
            return;
        }

        $this->geo = app(GeolocationService::class);
        mt_srand(20261024);

        $roles = Role::query()->pluck('id', 'name');
        $adminRole = RoleName::Admin->value;
        $supervisorRole = RoleName::Supervisor->value;
        $lecturerRole = RoleName::Lecturer->value;
        $studentRole = RoleName::Student->value;

        $admin = User::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Dr. Alice Kamau',
                'phone' => '+256 772 300 501',
                'password' => 'password',
                'role_id' => $roles[$adminRole],
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );

        $companyModels = [];
        $supervisorModels = [];
        $supervisorUsers = [];

        foreach ($this->companies as $index => $data) {
            $company = Company::updateOrCreate(['name' => $data['name']], $data + ['status' => 'active']);
            $companyModels[] = $company;

            $supervisor = $this->supervisors[$index];
            $user = User::updateOrCreate(
                ['email' => $supervisor['email']],
                [
                    'name' => $supervisor['name'],
                    'phone' => $supervisor['phone'],
                    'password' => 'password',
                    'role_id' => $roles[$supervisorRole],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $supervisorModels[] = CompanySupervisor::updateOrCreate(
                ['user_id' => $user->id],
                ['company_id' => $company->id, 'position' => $supervisor['position']],
            );
            $supervisorUsers[] = $user;
        }

        $lecturerModels = [];
        foreach ($this->lecturers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => 'password',
                    'role_id' => $roles[$lecturerRole],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $lecturerModels[] = Lecturer::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'department_id' => Department::where('code', $data['department'])->value('id'),
                    'staff_number' => $data['staff_number'],
                    'position' => $data['position'],
                ],
            );
        }

        $academicYear = AcademicYear::where('is_current', true)->firstOrFail();
        $activePeriod = InternshipPeriod::where('status', 'active')->firstOrFail();
        $closedPeriod = InternshipPeriod::where('status', 'closed')->firstOrFail();
        $today = now()->startOfDay();
        $lecturerByDepartment = [
            'CS' => 0,
            'IT' => 1,
            'ACC' => 2,
            'BUS' => 2,
            'EEE' => 3,
            'CIV' => 3,
        ];

        $studentModels = [];
        $internships = [];

        foreach ($this->students as $index => $data) {
            [$name, $email, $registration, $courseCode, $companyIndex] = $data;
            $course = Course::where('code', $courseCode)->firstOrFail();
            $department = Department::findOrFail($course->department_id);
            $faculty = $department->faculty;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => '+256 77'.rand(1, 9).' '.rand(100, 999).' '.rand(100, 999),
                    'password' => 'password',
                    'role_id' => $roles[$studentRole],
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                ],
            );

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'registration_number' => $registration,
                    'student_number' => 'STU-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'faculty_id' => $faculty->id,
                    'department_id' => $department->id,
                    'course_id' => $course->id,
                    'academic_year_id' => $academicYear->id,
                    'year_of_study' => $courseCode === 'BSC-CS' ? 3 : 3,
                ],
            );
            $studentModels[] = $student;

            $lecturerIndex = $lecturerByDepartment[$department->code] ?? 0;

            if ($index === 19) {
                $internship = Internship::create([
                    'student_id' => $student->id,
                    'company_id' => $companyModels[$companyIndex]->id,
                    'company_supervisor_id' => $supervisorModels[$companyIndex]->id,
                    'lecturer_id' => $lecturerModels[$lecturerIndex]->id,
                    'internship_period_id' => $closedPeriod->id,
                    'start_date' => $today->copy()->subDays(150)->toDateString(),
                    'end_date' => $today->copy()->subDays(90)->toDateString(),
                    'status' => 'completed',
                    'notes' => 'Completed internship; final report submitted.',
                ]);
            } elseif ($index === 18) {
                $internship = Internship::create([
                    'student_id' => $student->id,
                    'company_id' => $companyModels[$companyIndex]->id,
                    'company_supervisor_id' => $supervisorModels[$companyIndex]->id,
                    'lecturer_id' => $lecturerModels[$lecturerIndex]->id,
                    'internship_period_id' => $activePeriod->id,
                    'start_date' => $today->copy()->addDays(10)->toDateString(),
                    'end_date' => $today->copy()->addDays(50)->toDateString(),
                    'status' => 'pending',
                    'notes' => 'Placement approved by the university; awaiting company confirmation.',
                ]);
            } else {
                $internship = Internship::create([
                    'student_id' => $student->id,
                    'company_id' => $companyModels[$companyIndex]->id,
                    'company_supervisor_id' => $supervisorModels[$companyIndex]->id,
                    'lecturer_id' => $lecturerModels[$lecturerIndex]->id,
                    'internship_period_id' => $activePeriod->id,
                    'start_date' => $activePeriod->start_date->toDateString(),
                    'end_date' => $activePeriod->end_date->toDateString(),
                    'status' => 'active',
                ]);
            }

            $internships[$index] = $internship;
        }

        $attendanceProbability = [0.92, 0.96, 0.88, 0.91, 0.45, 0.93, 0.86, 0.90, 0.84, 0.88, 0.78, 0.82, 0.87, 0.63, 0.90, 0.85, 0.94, 0.89, 0.0, 0.0];
        $logProbability = [1.0, 0.90, 0.85, 0.92, 0.75, 0.88, 0.82, 0.90, 0.86, 0.80, 0.0, 0.78, 0.88, 0.70, 0.84, 0.80, 0.90, 0.86, 0.0, 0.0];

        foreach ($internships as $index => $internship) {
            if ($internship->status->value !== 'active') {
                continue;
            }

            $cursor = $internship->start_date->copy();

            while ($cursor->lt($today)) {
                if ($cursor->dayOfWeekIso <= 5 && (rand(1, 100) / 100) <= $attendanceProbability[$index]) {
                    $this->createAttendance($internship, $cursor, $logProbability[$index]);
                }

                $cursor->addDay();
            }

            if ($index !== 0
                && in_array((int) now()->dayOfWeekIso, array_map('intval', (array) settings('working_days', [1, 2, 3, 4, 5])), true)
                && now()->format('H:i') >= '07:30'
                && rand(1, 100) <= 62) {
                $this->createTodayAttendance($internship);
            }
        }

        $this->seedEvaluations($internships, $supervisorModels);
        $this->seedVisits($internships, $lecturerModels);
        $this->normalizeHealthyStudents($internships);

        $this->ensurePendingLogPerCompany($internships, $supervisorModels);
        $this->shapeDemoStudent($internships[0], $supervisorModels[0]);
        $this->removeRecentAttendance($internships[4]);

        $this->seedAuditTrail($admin, $supervisorUsers, $lecturerModels, $companyModels);
        $this->seedNotifications($internships, $supervisorModels, $lecturerModels);
    }

    private function createAttendance(Internship $internship, Carbon $date, float $logProbability): void
    {
        $company = $internship->company;
        $inside = rand(1, 100) > 4;
        $latitude = $company->latitude + ($inside ? rand(-750, 750) / 1000000 : rand(6000, 12000) / 1000000);
        $longitude = $company->longitude + ($inside ? rand(-750, 750) / 1000000 : rand(6000, 12000) / 1000000);
        $distance = $this->geo->distanceInMetres($latitude, $longitude, $company->latitude, $company->longitude);

        $late = rand(1, 100) <= 20;
        $checkIn = $date->copy()->setTime($late ? rand(8, 9) : rand(7, 8), $late ? rand(31, 59) : rand(0, 25));
        $lateAfter = $date->copy()->setTime(8, 30);

        if (! $late) {
            $checkIn = $date->copy()->setTime(7, rand(45, 59));
        }

        $lateMinutes = $checkIn->gt($lateAfter) ? $lateAfter->diffInMinutes($checkIn) : 0;

        $skipCheckout = rand(1, 100) <= 3;
        $shortDay = rand(1, 100) <= 6;
        $checkOut = null;
        $workedMinutes = null;
        $checkOutLatitude = null;
        $checkOutLongitude = null;
        $checkOutDistance = null;

        if (! $skipCheckout) {
            $checkOut = $date->copy()->setTime($shortDay ? rand(15, 16) : 17, rand(0, 40));
            $workedMinutes = max(0, (int) $checkIn->diffInMinutes($checkOut));
        }

        $rejected = rand(1, 100) <= 1;
        $status = match (true) {
            $rejected => AttendanceStatus::Rejected,
            ! $inside => AttendanceStatus::OutsideLocation,
            $skipCheckout => AttendanceStatus::Incomplete,
            $shortDay => AttendanceStatus::Incomplete,
            $lateMinutes > 0 => AttendanceStatus::Late,
            default => AttendanceStatus::Present,
        };

        if (! $skipCheckout) {
            $checkOutLatitude = $company->latitude + rand(-600, 600) / 1000000;
            $checkOutLongitude = $company->longitude + rand(-600, 600) / 1000000;
            $checkOutDistance = $this->geo->distanceInMetres($checkOutLatitude, $checkOutLongitude, $company->latitude, $company->longitude);
        }

        $notes = match ($status) {
            AttendanceStatus::OutsideLocation => sprintf('Check-in was %.0f m from the company; allowed radius is %d m.', $distance, $company->allowed_radius),
            AttendanceStatus::Incomplete => $skipCheckout ? 'No check-out was recorded for this day.' : 'Worked less than the required minimum hours.',
            AttendanceStatus::Rejected => 'Rejected by supervisor: the location evidence could not be verified.',
            default => null,
        };

        $attendance = Attendance::create([
            'student_id' => $internship->student_id,
            'internship_id' => $internship->id,
            'date' => $date->toDateString(),
            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => rand(8, 45),
            'distance_from_company' => $distance,
            'check_out_latitude' => $checkOutLatitude,
            'check_out_longitude' => $checkOutLongitude,
            'check_out_accuracy' => $skipCheckout ? null : rand(8, 45),
            'check_out_distance' => $checkOutDistance,
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'status' => $status,
            'verification_method' => $status === AttendanceStatus::Rejected ? VerificationMethod::Manual : VerificationMethod::Gps,
            'notes' => $notes,
            'reviewed_by' => $status === AttendanceStatus::Rejected ? $internship->supervisor->user_id : null,
            'reviewed_at' => $status === AttendanceStatus::Rejected ? $date->copy()->setTime(18, 0) : null,
        ]);

        if ($status === AttendanceStatus::Rejected) {
            $attendance->approvals()->create([
                'reviewer_id' => $internship->supervisor->user_id,
                'action' => 'rejected',
                'comments' => 'The location evidence could not be verified.',
            ]);
        }

        if (in_array($status, [AttendanceStatus::Present, AttendanceStatus::Late], true)
            && (rand(1, 100) / 100) <= $logProbability) {
            $this->createDailyLog($internship, $attendance);
        }
    }

    private function createTodayAttendance(Internship $internship): void
    {
        $company = $internship->company;
        $latitude = $company->latitude + rand(-500, 500) / 1000000;
        $longitude = $company->longitude + rand(-500, 500) / 1000000;
        $distance = $this->geo->distanceInMetres($latitude, $longitude, $company->latitude, $company->longitude);
        $checkIn = now()->copy()->subMinutes(rand(5, 90));
        $lateAfter = now()->copy()->setTimeFromTimeString((string) settings('late_after', '08:30'));
        $lateMinutes = $checkIn->gt($lateAfter) ? (int) $lateAfter->diffInMinutes($checkIn) : 0;

        Attendance::create([
            'student_id' => $internship->student_id,
            'internship_id' => $internship->id,
            'date' => now()->toDateString(),
            'check_in_time' => $checkIn,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'check_in_accuracy' => rand(8, 35),
            'distance_from_company' => $distance,
            'late_minutes' => $lateMinutes,
            'status' => $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present,
            'verification_method' => VerificationMethod::Gps,
        ]);
    }

    private function createDailyLog(Internship $internship, Attendance $attendance): void
    {
        $statusRoll = rand(1, 100);
        $status = match (true) {
            $statusRoll <= 80 => DailyLogStatus::Approved,
            $statusRoll <= 86 => DailyLogStatus::Submitted,
            $statusRoll <= 91 => DailyLogStatus::UnderReview,
            default => DailyLogStatus::Rejected,
        };

        $date = $attendance->date->copy();
        $supervisorUser = $internship->supervisor->user;
        $reviewed = in_array($status, [DailyLogStatus::Approved, DailyLogStatus::Rejected], true);
        $reason = $status === DailyLogStatus::Rejected
            ? 'The entry is too brief. Describe the tasks completed, the skills learned, and the evidence in more detail.'
            : null;

        $log = DailyLog::create([
            'student_id' => $internship->student_id,
            'internship_id' => $internship->id,
            'attendance_id' => $attendance->id,
            'date' => $date->toDateString(),
            'activities_performed' => $this->activities[array_rand($this->activities)],
            'skills_learned' => $this->skills[array_rand($this->skills)],
            'challenges' => $this->challenges[array_rand($this->challenges)],
            'solutions' => $this->solutions[array_rand($this->solutions)],
            'daily_reflection' => $this->reflections[array_rand($this->reflections)],
            'hours_worked' => round(($attendance->worked_minutes ?? 480) / 60, 1),
            'status' => $status,
            'submitted_at' => $date->copy()->setTime(17, 45),
            'reviewed_by' => $reviewed ? $supervisorUser->id : null,
            'reviewed_at' => $reviewed ? $date->copy()->addDay()->setTime(10, 0) : null,
            'rejection_reason' => $reason,
        ]);

        if ($reviewed) {
            DB::table('approvals')->insert([
                'subject_type' => DailyLog::class,
                'subject_id' => $log->id,
                'reviewer_id' => $supervisorUser->id,
                'action' => $status === DailyLogStatus::Approved ? 'approved' : 'rejected',
                'comments' => $reason,
                'created_at' => $date->copy()->addDay()->setTime(10, 0),
                'updated_at' => $date->copy()->addDay()->setTime(10, 0),
            ]);
        }
    }

    private function seedEvaluations(array $internships, array $supervisorModels): void
    {
        $scale = (int) settings('evaluation_scale', 5);
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->subWeek();

        foreach ($internships as $index => $internship) {
            if ($internship->status->value !== 'active' || $index === 10) {
                continue;
            }

            foreach ([0, 1] as $weeksAgo) {
                if ($index !== 0 && rand(1, 100) > 78) {
                    continue;
                }

                $start = $weekStart->copy()->subWeeks($weeksAgo);
                $end = $start->copy()->addDays(6);

                Evaluation::updateOrCreate(
                    ['internship_id' => $internship->id, 'week_start' => $start->toDateString()],
                    [
                        'company_supervisor_id' => $internship->company_supervisor_id,
                        'week_end' => $end->toDateString(),
                        'technical_skills' => rand(3, $scale),
                        'communication' => rand(3, $scale),
                        'professionalism' => rand(3, $scale),
                        'teamwork' => rand(3, $scale),
                        'punctuality' => rand(3, $scale),
                        'initiative' => rand(3, $scale),
                        'strengths' => 'Shows good initiative and communicates clearly with the team.',
                        'areas_for_improvement' => 'Continue to improve documentation detail and time management on busy days.',
                        'remarks' => 'Consistent progress observed during the week.',
                        'recommendation' => 'Recommended to continue the internship.',
                    ],
                );
            }
        }
    }

    private function seedVisits(array $internships, array $lecturerModels): void
    {
        foreach ($internships as $index => $internship) {
            if ($internship->status->value !== 'active') {
                continue;
            }

            $isFirstForLecturer = collect($internships)
                ->filter(fn (Internship $item, int $key) => $key < $index
                    && $item->status->value === 'active'
                    && $item->lecturer_id === $internship->lecturer_id)
                ->isEmpty();

            if ($isFirstForLecturer) {
                $completed = LecturerVisit::create([
                    'internship_id' => $internship->id,
                    'lecturer_id' => $internship->lecturer_id,
                    'visit_date' => now()->subDays(7)->toDateString(),
                    'purpose' => 'Routine monitoring visit',
                    'status' => 'completed',
                    'observations' => 'Student is settled into the workplace and has a clear task allocation.',
                    'student_performance' => 'Punctual, engaged, and able to explain the work completed so far.',
                    'company_feedback' => 'The supervisor reports good cooperation and willingness to learn.',
                    'recommendations' => 'Maintain the daily log discipline and gather evidence for major tasks.',
                    'action_items' => 'Student to share an updated learning plan with the lecturer.',
                    'completed_at' => now()->subDays(7)->setTime(15, 30),
                ]);

                $nextVisit = now()->addDays(4);

                if ($nextVisit->isWeekend()) {
                    $nextVisit = $nextVisit->next(Carbon::MONDAY);
                }

                LecturerVisit::create([
                    'internship_id' => $internship->id,
                    'lecturer_id' => $internship->lecturer_id,
                    'visit_date' => $nextVisit->toDateString(),
                    'purpose' => 'Mid-internship review',
                    'status' => 'scheduled',
                ]);
            } elseif ($index !== 18 && rand(1, 100) <= 18) {
                $visitDate = now()->addDays(rand(3, 12));

                if ($visitDate->isWeekend()) {
                    $visitDate = $visitDate->next(Carbon::MONDAY);
                }

                LecturerVisit::create([
                    'internship_id' => $internship->id,
                    'lecturer_id' => $internship->lecturer_id,
                    'visit_date' => $visitDate->toDateString(),
                    'purpose' => 'Progress and welfare check',
                    'status' => 'scheduled',
                ]);
            }
        }
    }

    private function normalizeHealthyStudents(array $internships): void
    {
        foreach ([1, 2, 3, 7, 8] as $index) {
            $internship = $internships[$index] ?? null;

            if (! $internship || $internship->status->value !== 'active') {
                continue;
            }

            $company = $internship->company;
            $supervisorUserId = $internship->supervisor->user_id;
            $recentWorkingDays = collect();
            $cursor = now()->startOfDay()->subDay();

            while ($recentWorkingDays->count() < 3 && $cursor->gte($internship->start_date)) {
                if ($cursor->dayOfWeekIso <= 5) {
                    $recentWorkingDays->push($cursor->copy());
                }

                $cursor->subDay();
            }

            foreach ($recentWorkingDays as $date) {
                $attendance = $internship->attendances()
                    ->whereDate('date', $date->toDateString())
                    ->first();

                if (! $attendance) {
                    $attendance = Attendance::create([
                        'student_id' => $internship->student_id,
                        'internship_id' => $internship->id,
                        'date' => $date->toDateString(),
                        'check_in_time' => $date->copy()->setTime(7, rand(50, 59)),
                        'check_in_latitude' => $company->latitude,
                        'check_in_longitude' => $company->longitude,
                        'check_in_accuracy' => rand(8, 25),
                        'distance_from_company' => 0,
                        'late_minutes' => 0,
                        'status' => AttendanceStatus::Present,
                        'verification_method' => VerificationMethod::Gps,
                    ]);
                }

                $this->completeHealthyAttendance($attendance, $company);
            }

            $internship->attendances()
                ->where('status', AttendanceStatus::Incomplete)
                ->get()
                ->each(fn (Attendance $attendance) => $this->completeHealthyAttendance($attendance, $company));

            foreach ($internship->attendances()->attended()->get() as $attendance) {
                if ($attendance->date->isToday()) {
                    continue;
                }

                $hasLog = $internship->dailyLogs()
                    ->where('attendance_id', $attendance->id)
                    ->exists();

                if (! $hasLog) {
                    $this->createDailyLog($internship, $attendance);
                }
            }

            foreach ($internship->dailyLogs()->get() as $log) {
                $reviewedAt = $log->date->copy()->addDay()->setTime(10, 0);

                if ($reviewedAt->gt(now())) {
                    $reviewedAt = now()->subMinutes(30);
                }

                $log->approvals()->delete();
                $log->update([
                    'status' => DailyLogStatus::Approved,
                    'reviewed_by' => $supervisorUserId,
                    'reviewed_at' => $reviewedAt,
                    'rejection_reason' => null,
                ]);

                $log->approvals()->create([
                    'reviewer_id' => $supervisorUserId,
                    'action' => 'approved',
                    'comments' => 'Consistent detail and reflection. Approved.',
                ]);
            }
        }
    }

    private function completeHealthyAttendance(Attendance $attendance, Company $company): void
    {
        $date = $attendance->date->copy();
        $checkIn = $attendance->check_in_time?->copy() ?? $date->copy()->setTime(7, 55);
        $checkOut = $date->copy()->setTime(17, rand(0, 15));
        $latitude = $company->latitude + rand(-200, 200) / 1000000;
        $longitude = $company->longitude + rand(-200, 200) / 1000000;

        $attendance->approvals()->delete();
        $attendance->update([
            'check_out_time' => $checkOut,
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'check_out_accuracy' => rand(8, 25),
            'check_out_distance' => $this->geo->distanceInMetres($latitude, $longitude, $company->latitude, $company->longitude),
            'worked_minutes' => max(0, (int) $checkIn->diffInMinutes($checkOut)),
            'status' => $attendance->late_minutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present,
            'notes' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    private function ensurePendingLogPerCompany(array $internships, array $supervisorModels): void
    {
        foreach ($supervisorModels as $supervisor) {
            $companyInternships = collect($internships)->filter(fn (Internship $internship) => $internship->company_supervisor_id === $supervisor->id && $internship->status->value === 'active');

            $pending = DailyLog::query()
                ->whereIn('internship_id', $companyInternships->pluck('id'))
                ->pendingReview()
                ->exists();

            if ($pending || $companyInternships->isEmpty()) {
                continue;
            }

            $log = DailyLog::query()
                ->whereIn('internship_id', $companyInternships->pluck('id'))
                ->latest('date')
                ->first();

            if (! $log) {
                continue;
            }

            $log->approvals()->delete();
            $log->update([
                'status' => DailyLogStatus::Submitted,
                'submitted_at' => now()->subDay(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]);
        }
    }

    private function shapeDemoStudent(Internship $internship, CompanySupervisor $supervisor): void
    {
        $supervisorUser = $supervisor->user;

        $logs = DailyLog::query()
            ->where('internship_id', $internship->id)
            ->orderByDesc('date')
            ->take(4)
            ->get();

        if ($logs->isEmpty()) {
            return;
        }

        $logs->each(fn (DailyLog $log) => $log->approvals()->delete());

        $latest = $logs->get(0);
        $latest?->update([
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now()->subHours(3),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);

        $rejected = $logs->get(1);
        $rejected?->update([
            'status' => DailyLogStatus::Rejected,
            'reviewed_by' => $supervisorUser->id,
            'reviewed_at' => now()->subDay()->setTime(11, 0),
            'rejection_reason' => 'Please expand the reflection and attach evidence for the main task completed.',
        ]);

        if ($rejected) {
            DB::table('approvals')->insert([
                'subject_type' => DailyLog::class,
                'subject_id' => $rejected->id,
                'reviewer_id' => $supervisorUser->id,
                'action' => 'correction_requested',
                'comments' => 'Please expand the reflection and attach evidence for the main task completed.',
                'created_at' => now()->subDay()->setTime(11, 0),
                'updated_at' => now()->subDay()->setTime(11, 0),
            ]);
        }

        $approved = $logs->get(2);
        $approved?->update([
            'status' => DailyLogStatus::Approved,
            'reviewed_by' => $supervisorUser->id,
            'reviewed_at' => now()->subDays(2)->setTime(9, 30),
            'rejection_reason' => null,
        ]);

        if ($approved) {
            DB::table('approvals')->insert([
                'subject_type' => DailyLog::class,
                'subject_id' => $approved->id,
                'reviewer_id' => $supervisorUser->id,
                'action' => 'approved',
                'comments' => 'Good detail and clear reflection. Keep it up.',
                'created_at' => now()->subDays(2)->setTime(9, 30),
                'updated_at' => now()->subDays(2)->setTime(9, 30),
            ]);
        }

        $missedCandidate = DailyLog::query()
            ->where('internship_id', $internship->id)
            ->whereDate('date', '<', now()->subDays(3)->toDateString())
            ->orderByDesc('date')
            ->first();

        if ($missedCandidate) {
            $missedCandidate->approvals()->delete();
            $missedCandidate->forceDelete();
        }
    }

    private function removeRecentAttendance(Internship $internship): void
    {
        $dates = collect();
        $cursor = now()->startOfDay()->subDay();

        while ($dates->count() < 3 && $cursor->gte($internship->start_date)) {
            if ($cursor->dayOfWeekIso <= 5) {
                $dates->push($cursor->toDateString());
            }

            $cursor->subDay();
        }

        DailyLog::query()
            ->where('internship_id', $internship->id)
            ->whereIn(DB::raw('DATE(date)'), $dates->all())
            ->forceDelete();

        Attendance::query()
            ->where('internship_id', $internship->id)
            ->whereIn(DB::raw('DATE(date)'), $dates->all())
            ->delete();
    }

    private function seedAuditTrail(User $admin, array $supervisorUsers, array $lecturerModels, array $companyModels): void
    {
        $entries = [
            [$admin->id, 'company.created', 'companies', 'Created company '.$companyModels[0]->name.'.', 20],
            [$admin->id, 'student.created', 'students', 'Created the student register for the current internship period.', 18],
            [$admin->id, 'internship.created', 'internships', 'Assigned 18 students to active internship placements.', 15],
            [$supervisorUsers[0]->id, 'log.approved', 'approvals', 'Approved daily logs for TechFlow students.', 2],
            [$supervisorUsers[2]->id, 'attendance.verified', 'attendance', 'Verified a flagged attendance record at Apex Financial Services.', 1],
            [$lecturerModels[0]->user_id, 'visit.created', 'visits', 'Scheduled a mid-internship review visit.', 3],
            [$admin->id, 'settings.updated', 'settings', 'Updated attendance and working-hour settings.', 1],
        ];

        foreach ($entries as [$userId, $action, $module, $description, $daysAgo]) {
            $timestamp = now()->subDays($daysAgo)->setTime(rand(9, 16), rand(0, 59));
            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'description' => $description,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'InternTrack demo seeder',
                'created_at' => $timestamp,
            ]);
        }
    }

    private function seedNotifications(array $internships, array $supervisorModels, array $lecturerModels): void
    {
        $demoInternship = $internships[0];
        $demoStudent = $demoInternship->student->user;
        $pendingLog = DailyLog::query()
            ->where('internship_id', $demoInternship->id)
            ->where('status', DailyLogStatus::Submitted)
            ->latest('date')
            ->first();

        $notifications = [
            [
                'user' => $supervisorModels[0]->user,
                'type' => LogSubmittedNotification::class,
                'data' => [
                    'title' => 'New daily log submitted',
                    'message' => $pendingLog
                        ? $demoStudent->name.' submitted a daily log for '.$pendingLog->date->format('d M Y').'.'
                        : 'A student submitted a new daily log.',
                    'url' => $pendingLog ? '/supervisor/approvals/'.$pendingLog->id : '/supervisor/approvals',
                    'icon' => 'bi-journal-text',
                ],
                'hours_ago' => 3,
                'read' => false,
            ],
            [
                'user' => $demoStudent,
                'type' => LogReviewedNotification::class,
                'data' => [
                    'title' => 'Correction requested for your daily log',
                    'message' => 'Your supervisor requested more detail in a recent daily log.',
                    'url' => '/student/logs',
                    'icon' => 'bi-exclamation-circle',
                ],
                'hours_ago' => 20,
                'read' => false,
            ],
            [
                'user' => $lecturerModels[0]->user,
                'type' => RiskAlertNotification::class,
                'data' => [
                    'title' => 'Students needing attention',
                    'message' => 'There are students with attendance or logbook concerns in your assigned group.',
                    'url' => '/lecturer/students',
                    'icon' => 'bi-exclamation-triangle',
                ],
                'hours_ago' => 30,
                'read' => true,
            ],
            [
                'user' => User::where('email', 'admin@test.com')->first(),
                'type' => BaseNotification::class,
                'data' => [
                    'title' => 'Welcome to InternTrack',
                    'message' => 'The internship workspace is ready. Review active placements and risk alerts.',
                    'url' => '/admin/dashboard',
                    'icon' => 'bi-grid',
                ],
                'hours_ago' => 48,
                'read' => true,
            ],
        ];

        foreach ($notifications as $item) {
            if (! $item['user']) {
                continue;
            }

            DB::table('notifications')->insert([
                'id' => Str::uuid()->toString(),
                'type' => $item['type'],
                'notifiable_type' => User::class,
                'notifiable_id' => $item['user']->id,
                'data' => json_encode($item['data']),
                'read_at' => $item['read'] ? now()->subHours($item['hours_ago'] - 1) : null,
                'created_at' => now()->subHours($item['hours_ago']),
                'updated_at' => now()->subHours($item['hours_ago']),
            ]);
        }
    }
}
