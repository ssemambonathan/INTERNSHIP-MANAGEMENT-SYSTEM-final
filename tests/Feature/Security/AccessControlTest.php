<?php

namespace Tests\Feature\Security;

use App\Enums\DailyLogStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\EvidenceFile;
use App\Models\Role;
use App\Models\User;
use App\Notifications\LogSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesInternshipContext;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use CreatesInternshipContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpInternshipContext();
    }

    public function test_guests_are_redirected_to_login_from_protected_areas(): void
    {
        foreach (['/dashboard', '/student/dashboard', '/supervisor/dashboard', '/lecturer/dashboard', '/admin/dashboard'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_each_role_is_confined_to_its_own_area(): void
    {
        $placement = $this->createPlacement();

        $this->actingAs($placement['studentUser'])->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($placement['studentUser'])->get('/lecturer/dashboard')->assertForbidden();
        $this->actingAs($placement['studentUser'])->get('/supervisor/dashboard')->assertForbidden();

        $this->actingAs($placement['supervisorUser'])->get('/student/dashboard')->assertForbidden();
        $this->actingAs($placement['supervisorUser'])->get('/admin/dashboard')->assertForbidden();

        $this->actingAs($placement['lecturerUser'])->get('/student/dashboard')->assertForbidden();
        $this->actingAs($placement['lecturerUser'])->get('/admin/dashboard')->assertForbidden();

        $this->actingAs($this->createAdminUser())->get('/student/dashboard')->assertForbidden();
        $this->actingAs($this->createAdminUser())->get('/admin/dashboard')->assertOk();
    }

    public function test_inactive_accounts_are_logged_out_of_protected_routes(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Inactive]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@test.test',
            'password' => 'password',
            'status' => UserStatus::Inactive,
        ]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_student_cannot_view_another_students_log(): void
    {
        $owner = $this->createPlacement();
        $intruder = $this->createPlacement();

        $log = $this->createDailyLogRecord($owner['internship']);

        $this->actingAs($intruder['studentUser'])
            ->get(route('student.logs.show', $log))
            ->assertForbidden();

        $this->actingAs($owner['studentUser'])
            ->get(route('student.logs.show', $log))
            ->assertOk();
    }

    public function test_a_student_cannot_edit_an_approved_log(): void
    {
        $placement = $this->createPlacement();
        $log = $this->createDailyLogRecord($placement['internship'], [
            'status' => DailyLogStatus::Approved,
        ]);

        $this->actingAs($placement['studentUser'])
            ->put(route('student.logs.update', $log), [
                'date' => today()->toDateString(),
                'action' => 'draft',
                'activities_performed' => 'Attempting to rewrite an approved record after the fact.',
            ])
            ->assertForbidden();
    }

    public function test_evidence_download_is_limited_to_authorized_viewers(): void
    {
        Storage::fake('local');

        $owner = $this->createPlacement();
        $intruder = $this->createPlacement();

        $log = $this->createDailyLogRecord($owner['internship']);

        Storage::disk('local')->put('evidence/'.$log->id.'/proof.pdf', 'pdf-bytes');

        $evidence = EvidenceFile::create([
            'attachable_type' => $log->getMorphClass(),
            'attachable_id' => $log->id,
            'original_name' => 'proof.pdf',
            'path' => 'evidence/'.$log->id.'/proof.pdf',
            'mime_type' => 'application/pdf',
            'size' => 9,
            'uploaded_by' => $owner['studentUser']->id,
        ]);

        $this->actingAs($intruder['studentUser'])
            ->get(route('evidence.download', $evidence))
            ->assertForbidden();

        $this->actingAs($owner['supervisorUser'])
            ->get(route('evidence.download', $evidence))
            ->assertOk();
    }

    public function test_a_supervisor_cannot_review_another_companys_log(): void
    {
        $logOwner = $this->createPlacement();
        $unrelated = $this->createPlacement();

        $log = $this->createDailyLogRecord($logOwner['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($unrelated['supervisorUser'])
            ->post(route('supervisor.approvals.approve', $log))
            ->assertForbidden();

        $this->assertSame(DailyLogStatus::Submitted, $log->fresh()->status);
    }

    public function test_a_lecturer_cannot_view_an_unassigned_student(): void
    {
        $placement = $this->createPlacement();
        $unrelated = $this->createPlacement();

        $this->actingAs($unrelated['lecturerUser'])
            ->get(route('lecturer.students.show', $placement['student']))
            ->assertForbidden();

        $this->actingAs($unrelated['lecturerUser'])
            ->get(route('lecturer.reports.student', $placement['student']))
            ->assertForbidden();
    }

    public function test_profile_updates_cannot_escalate_role_or_status(): void
    {
        $user = $this->createStudentUser()['user'];
        $adminRole = Role::firstOrCreate(
            ['name' => RoleName::Admin->value],
            ['label' => RoleName::Admin->label()],
        );

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'phone' => '+256 700 123 456',
                'role_id' => $adminRole->id,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isActive());
    }

    public function test_audit_logs_have_no_mutation_routes(): void
    {
        $this->assertFalse(Route::has('admin.audit-logs.update'));
        $this->assertFalse(Route::has('admin.audit-logs.destroy'));
    }

    public function test_notifications_are_scoped_to_the_signed_in_user(): void
    {
        $owner = $this->createPlacement();
        $other = $this->createPlacement();

        $log = $this->createDailyLogRecord($owner['internship'], [
            'status' => DailyLogStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $owner['supervisorUser']->notify(new LogSubmittedNotification($log));
        $notification = $owner['supervisorUser']->notifications()->firstOrFail();

        $this->actingAs($other['supervisorUser'])
            ->post(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($owner['supervisorUser'])
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
