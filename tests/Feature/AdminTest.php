<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_admin_can_register_a_company_with_a_geofence(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'Nile Innovations',
            'industry' => 'Fintech',
            'address' => 'Plot 4 Kampala Road',
            'city' => 'Kampala',
            'phone' => '+256 414 555 000',
            'email' => 'info@nile.test',
            'latitude' => 0.3152,
            'longitude' => 32.5825,
            'allowed_radius' => 200,
            'status' => 'active',
        ])->assertRedirect();

        $company = Company::where('name', 'Nile Innovations')->firstOrFail();

        $this->assertSame(200, $company->allowed_radius);
        $this->assertDatabaseHas('audit_logs', ['action' => 'company.created', 'record_id' => $company->id]);
    }

    public function test_admin_company_validation_rejects_invalid_coordinates_and_radius(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.companies.store'), [
            'name' => 'Invalid Company',
            'industry' => 'Technology',
            'address' => 'Somewhere',
            'city' => 'Kampala',
            'latitude' => 120,
            'longitude' => 500,
            'allowed_radius' => 10,
            'status' => 'active',
        ])->assertSessionHasErrors(['latitude', 'longitude', 'allowed_radius']);

        $this->assertSame(1, Company::count());
    }

    public function test_admin_can_create_a_student_account_with_a_hashed_password(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Martha Achieng',
            'email' => 'martha.achieng@student.test',
            'phone' => '+256 700 111 222',
            'password' => 'Str0ngPass!',
            'password_confirmation' => 'Str0ngPass!',
            'registration_number' => '2026/CS/500',
            'student_number' => 'STU-2026-500',
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'course_id' => $this->course->id,
            'academic_year_id' => $this->academicYear->id,
            'year_of_study' => 3,
            'status' => 'active',
        ])->assertRedirect();

        $student = Student::where('registration_number', '2026/CS/500')->firstOrFail();

        $this->assertSame('Martha Achieng', $student->user->name);
        $this->assertTrue($student->user->isStudent());
        $this->assertTrue(Hash::check('Str0ngPass!', $student->user->password));
        $this->assertNotSame('Str0ngPass!', $student->user->password);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.created']);
    }

    public function test_admin_student_creation_rejects_a_mismatched_academic_structure(): void
    {
        $admin = $this->createAdminUser();

        $otherFaculty = Faculty::create(['name' => 'Faculty of Business', 'code' => 'FOB']);

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Mismatch Student',
            'email' => 'mismatch@student.test',
            'password' => 'Str0ngPass!',
            'password_confirmation' => 'Str0ngPass!',
            'registration_number' => '2026/CS/501',
            'faculty_id' => $otherFaculty->id,
            'department_id' => $this->department->id,
            'course_id' => $this->course->id,
            'year_of_study' => 3,
        ])->assertSessionHasErrors('department_id');

        $this->assertFalse(User::where('email', 'mismatch@student.test')->exists());
    }

    public function test_admin_can_import_students_from_csv(): void
    {
        $admin = $this->createAdminUser();

        $csv = <<<'CSV'
name,email,password,phone,registration_number,student_number,faculty_code,department_code,course_code,year_of_study,academic_year
Imported One,import.one@student.test,Password123!,,REG-IMP-001,STU-IMP-001,FOC,CS,BCS,3,2026/2027
Imported Two,import.two@student.test,Password123!,,REG-IMP-002,STU-IMP-002,FOC,CS,BCS,2,2026/2027
CSV;

        $this->actingAs($admin)->post(route('admin.students.import'), [
            'file' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ])->assertRedirect(route('admin.students.index'));

        $this->assertSame(2, Student::whereIn('registration_number', ['REG-IMP-001', 'REG-IMP-002'])->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.imported']);
    }

    public function test_admin_can_update_system_settings_and_the_change_is_audited(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => [
                'university_name' => 'Metropolitan University Uganda',
                'university_address' => 'Plot 12 University Road, Kampala',
                'university_email' => 'internships@metropolitan.ac.ug',
                'university_phone' => '+256 414 000 000',
                'working_days' => [1, 2, 3, 4, 5, 6],
                'working_start_time' => '08:00',
                'working_end_time' => '17:00',
                'late_after' => '08:45',
                'minimum_working_hours' => 7.5,
                'default_gps_radius' => 180,
                'location_accuracy_warning' => 100,
                'max_upload_size_kb' => 10240,
                'evaluation_scale' => 5,
                'mail_notifications_enabled' => '1',
            ],
        ])->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('08:45', SystemSetting::get('late_after'));
        $this->assertSame(180, SystemSetting::get('default_gps_radius'));
        $this->assertSame([1, 2, 3, 4, 5, 6], SystemSetting::get('working_days'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
    }

    public function test_settings_reject_a_late_threshold_outside_working_hours(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'settings' => [
                'university_name' => 'Metropolitan University Uganda',
                'university_address' => 'Plot 12 University Road, Kampala',
                'university_email' => 'internships@metropolitan.ac.ug',
                'working_days' => [1, 2, 3, 4, 5],
                'working_start_time' => '08:00',
                'working_end_time' => '17:00',
                'late_after' => '18:00',
                'minimum_working_hours' => 7.5,
                'default_gps_radius' => 150,
                'location_accuracy_warning' => 100,
                'max_upload_size_kb' => 10240,
                'evaluation_scale' => 5,
            ],
        ])->assertSessionHasErrors('settings.late_after');

        $this->assertSame('08:30', SystemSetting::get('late_after'));
    }

    public function test_audit_log_view_is_admin_only(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->get(route('admin.audit-logs.index'))->assertForbidden();

        $this->actingAs($this->createAdminUser())->get(route('admin.audit-logs.index'))->assertOk();
    }

    public function test_admin_can_toggle_a_student_account_status(): void
    {
        $admin = $this->createAdminUser();
        $student = $this->createStudentUser()['student'];

        $this->actingAs($admin)
            ->post(route('admin.students.toggle-status', $student))
            ->assertRedirect();

        $this->assertFalse($student->user->fresh()->isActive());
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.status_changed']);
    }
}
