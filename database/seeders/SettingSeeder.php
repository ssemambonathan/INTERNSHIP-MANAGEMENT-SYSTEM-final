<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['university_name', 'Metropolitan University Uganda', 'string', 'university', 'University name'],
            ['university_address', 'Plot 12 University Road, Kampala, Uganda', 'string', 'university', 'University address'],
            ['university_email', 'internships@metropolitan.ac.ug', 'string', 'university', 'University email'],
            ['university_phone', '+256 414 000 000', 'string', 'university', 'University phone'],
            ['university_logo', null, 'string', 'university', 'University logo'],
            ['working_days', ['1', '2', '3', '4', '5'], 'json', 'internship', 'Working days (ISO weekday numbers)'],
            ['working_start_time', '08:00', 'string', 'internship', 'Expected start time'],
            ['working_end_time', '17:00', 'string', 'internship', 'Expected end time'],
            ['late_after', '08:30', 'string', 'attendance', 'Late after'],
            ['minimum_working_hours', 7.5, 'decimal', 'attendance', 'Minimum working hours'],
            ['default_gps_radius', 150, 'integer', 'attendance', 'Default GPS radius (metres)'],
            ['location_accuracy_warning', 100, 'integer', 'attendance', 'GPS accuracy warning threshold (metres)'],
            ['max_upload_size_kb', 10240, 'integer', 'uploads', 'Maximum evidence upload size (KB)'],
            ['evaluation_scale', 5, 'integer', 'evaluation', 'Weekly evaluation scale (maximum score)'],
            ['mail_notifications_enabled', true, 'boolean', 'notifications', 'Send email notifications'],
        ];

        foreach ($settings as [$key, $value, $type, $group, $label]) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($value) ? json_encode($value) : ($value === null ? null : (is_bool($value) ? ($value ? '1' : '0') : (string) $value)),
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                ],
            );
        }

        SystemSetting::flushCache();
    }
}
