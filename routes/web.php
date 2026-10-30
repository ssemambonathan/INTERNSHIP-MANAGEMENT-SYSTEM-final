<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\Lecturer;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Supervisor;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'active'])
    ->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/evidence/{evidence}', [EvidenceController::class, 'download'])->name('evidence.download');
});

Route::middleware(['auth', 'active', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/attendance', [Student\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/check-in', [Student\AttendanceController::class, 'checkIn'])
            ->middleware('throttle:10,1')->name('attendance.check-in');
        Route::post('/attendance/check-out', [Student\AttendanceController::class, 'checkOut'])
            ->middleware('throttle:10,1')->name('attendance.check-out');

        Route::resource('logs', Student\LogController::class)->except(['destroy']);
        Route::post('/logs/{log}/submit', [Student\LogController::class, 'submit'])->name('logs.submit');
        Route::post('/logs/{log}/evidence', [Student\LogController::class, 'uploadEvidence'])->name('logs.evidence.store');
        Route::delete('/evidence/{evidence}', [Student\LogController::class, 'deleteEvidence'])->name('logs.evidence.destroy');

        Route::get('/report', [Student\ReportController::class, 'show'])->name('report.show');
        Route::get('/report/download', [Student\ReportController::class, 'download'])->name('report.download');
    });

Route::middleware(['auth', 'active', 'role:supervisor'])
    ->prefix('supervisor')
    ->name('supervisor.')
    ->group(function () {
        Route::get('/dashboard', [Supervisor\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [Supervisor\StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [Supervisor\StudentController::class, 'show'])->name('students.show');

        Route::get('/approvals', [Supervisor\ApprovalController::class, 'index'])->name('approvals.index');
        Route::get('/approvals/{log}', [Supervisor\ApprovalController::class, 'show'])->name('approvals.show');
        Route::post('/approvals/{log}/review', [Supervisor\ApprovalController::class, 'startReview'])->name('approvals.review');
        Route::post('/approvals/{log}/approve', [Supervisor\ApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{log}/reject', [Supervisor\ApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('/approvals/{log}/comment', [Supervisor\ApprovalController::class, 'comment'])->name('approvals.comment');

        Route::get('/attendance', [Supervisor\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/{attendance}/verify', [Supervisor\AttendanceController::class, 'verify'])->name('attendance.verify');
        Route::post('/attendance/{attendance}/reject', [Supervisor\AttendanceController::class, 'reject'])->name('attendance.reject');

        Route::resource('evaluations', Supervisor\EvaluationController::class)->except(['destroy']);
    });

Route::middleware(['auth', 'active', 'role:lecturer'])
    ->prefix('lecturer')
    ->name('lecturer.')
    ->group(function () {
        Route::get('/dashboard', [Lecturer\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/students', [Lecturer\StudentController::class, 'index'])->name('students.index');
        Route::get('/students/{student}', [Lecturer\StudentController::class, 'show'])->name('students.show');
        Route::post('/students/{internship}/reopen-date', [Lecturer\StudentController::class, 'reopenDate'])->name('students.reopen');

        Route::resource('visits', Lecturer\VisitController::class);
        Route::post('/visits/{visit}/complete', [Lecturer\VisitController::class, 'complete'])->name('visits.complete');
        Route::post('/visits/{visit}/evidence', [Lecturer\VisitController::class, 'uploadEvidence'])->name('visits.evidence.store');
        Route::delete('/visits/evidence/{evidence}', [Lecturer\VisitController::class, 'deleteEvidence'])->name('visits.evidence.destroy');

        Route::get('/students/{student}/report', [Lecturer\ReportController::class, 'show'])->name('reports.student');
        Route::get('/students/{student}/report/download', [Lecturer\ReportController::class, 'download'])->name('reports.student.download');
    });

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/students/import', [Admin\StudentController::class, 'importForm'])->name('students.import.form');
        Route::get('/students/import/template', [Admin\StudentController::class, 'template'])->name('students.import.template');
        Route::post('/students/import', [Admin\StudentController::class, 'import'])->name('students.import');
        Route::post('/students/{student}/toggle-status', [Admin\StudentController::class, 'toggleStatus'])->name('students.toggle-status');
        Route::post('/students/{student}/reopen-log-date', [Admin\StudentController::class, 'reopenDate'])->name('students.reopen');
        Route::resource('students', Admin\StudentController::class)->except(['destroy']);

        Route::post('/companies/{company}/toggle-status', [Admin\CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
        Route::resource('companies', Admin\CompanyController::class)->except(['destroy']);

        Route::post('/supervisors/{supervisor}/toggle-status', [Admin\SupervisorController::class, 'toggleStatus'])->name('supervisors.toggle-status');
        Route::resource('supervisors', Admin\SupervisorController::class)->except(['destroy']);

        Route::post('/lecturers/{lecturer}/toggle-status', [Admin\LecturerController::class, 'toggleStatus'])->name('lecturers.toggle-status');
        Route::resource('lecturers', Admin\LecturerController::class)->except(['destroy']);

        Route::post('/internships/{internship}/status', [Admin\InternshipController::class, 'updateStatus'])->name('internships.status');
        Route::resource('internships', Admin\InternshipController::class)->except(['destroy']);

        Route::post('/periods/{period}/toggle-status', [Admin\InternshipPeriodController::class, 'toggleStatus'])->name('periods.toggle-status');
        Route::resource('periods', Admin\InternshipPeriodController::class)->except(['show', 'destroy']);

        Route::get('/academics', [Admin\AcademicStructureController::class, 'index'])->name('academics.index');
        Route::post('/academics/faculties', [Admin\AcademicStructureController::class, 'storeFaculty'])->name('academics.faculties.store');
        Route::patch('/academics/faculties/{faculty}', [Admin\AcademicStructureController::class, 'updateFaculty'])->name('academics.faculties.update');
        Route::post('/academics/departments', [Admin\AcademicStructureController::class, 'storeDepartment'])->name('academics.departments.store');
        Route::patch('/academics/departments/{department}', [Admin\AcademicStructureController::class, 'updateDepartment'])->name('academics.departments.update');
        Route::post('/academics/courses', [Admin\AcademicStructureController::class, 'storeCourse'])->name('academics.courses.store');
        Route::patch('/academics/courses/{course}', [Admin\AcademicStructureController::class, 'updateCourse'])->name('academics.courses.update');
        Route::post('/academics/academic-years', [Admin\AcademicStructureController::class, 'storeAcademicYear'])->name('academics.years.store');
        Route::patch('/academics/academic-years/{academicYear}', [Admin\AcademicStructureController::class, 'updateAcademicYear'])->name('academics.years.update');

        Route::get('/attendance', [Admin\AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance/{attendance}/correct', [Admin\AttendanceController::class, 'correct'])->name('attendance.correct');

        Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');
    });

require __DIR__.'/auth.php';
