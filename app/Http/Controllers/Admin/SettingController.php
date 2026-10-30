<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function edit(): View
    {
        $settings = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');

        $groups = [
            'university' => ['label' => 'University identity', 'icon' => 'bi-building'],
            'internship' => ['label' => 'Working week', 'icon' => 'bi-calendar-week'],
            'attendance' => ['label' => 'Attendance & geofencing', 'icon' => 'bi-geo-alt'],
            'uploads' => ['label' => 'Evidence uploads', 'icon' => 'bi-paperclip'],
            'evaluation' => ['label' => 'Evaluation', 'icon' => 'bi-clipboard-data'],
            'notifications' => ['label' => 'Notifications', 'icon' => 'bi-bell'],
        ];

        return view('admin.settings.edit', compact('settings', 'groups'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.university_name' => ['required', 'string', 'max:150'],
            'settings.university_address' => ['required', 'string', 'max:255'],
            'settings.university_email' => ['required', 'email', 'max:150'],
            'settings.university_phone' => ['nullable', 'string', 'max:30'],
            'settings.working_days' => ['required', 'array', 'min:1'],
            'settings.working_days.*' => ['integer', 'between:1,7'],
            'settings.working_start_time' => ['required', 'date_format:H:i'],
            'settings.working_end_time' => ['required', 'date_format:H:i'],
            'settings.late_after' => ['required', 'date_format:H:i'],
            'settings.minimum_working_hours' => ['required', 'numeric', 'between:1,16'],
            'settings.default_gps_radius' => ['required', 'integer', 'between:50,5000'],
            'settings.location_accuracy_warning' => ['required', 'integer', 'between:10,1000'],
            'settings.max_upload_size_kb' => ['required', 'integer', 'between:1024,20480'],
            'settings.evaluation_scale' => ['required', 'integer', 'between:3,10'],
            'settings.mail_notifications_enabled' => ['nullable', 'boolean'],
        ], [
            'settings.working_days.min' => 'Select at least one working day.',
            'settings.working_days.*.between' => 'Working days must be ISO weekday numbers between 1 and 7.',
            'settings.minimum_working_hours.between' => 'Minimum working hours must be between 1 and 16.',
            'settings.max_upload_size_kb.between' => 'The upload limit must be between 1 MB and 20 MB.',
            'settings.evaluation_scale.between' => 'The evaluation scale must be between 3 and 10.',
        ]);

        $values = $data['settings'];

        $start = Carbon::createFromFormat('H:i', $values['working_start_time']);
        $end = Carbon::createFromFormat('H:i', $values['working_end_time']);
        $late = Carbon::createFromFormat('H:i', $values['late_after']);

        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'settings.working_end_time' => 'The expected end time must be after the start time.',
            ]);
        }

        if ($late->lt($start) || $late->gt($end)) {
            throw ValidationException::withMessages([
                'settings.late_after' => 'The late threshold must fall between the expected start and end time.',
            ]);
        }

        $workingDays = array_values(array_unique(array_map('intval', $values['working_days'])));
        sort($workingDays);

        $values['working_days'] = $workingDays;
        $values['mail_notifications_enabled'] = $request->boolean('settings.mail_notifications_enabled');

        $allowed = [
            'university_name', 'university_address', 'university_email', 'university_phone',
            'working_days', 'working_start_time', 'working_end_time',
            'late_after', 'minimum_working_hours', 'default_gps_radius', 'location_accuracy_warning',
            'max_upload_size_kb', 'evaluation_scale', 'mail_notifications_enabled',
        ];

        DB::transaction(function () use ($values, $allowed) {
            foreach ($allowed as $key) {
                if (! array_key_exists($key, $values)) {
                    continue;
                }

                SystemSetting::set($key, $values[$key]);
            }
        });

        SystemSetting::flushCache();

        $this->audit->record(
            'settings.updated',
            'settings',
            sprintf('%s updated the system settings.', $request->user()->name),
            null,
            $request->user(),
        );

        return redirect()->route('admin.settings.edit')->with('success', 'System settings saved.');
    }
}
