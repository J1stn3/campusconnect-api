<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $student = User::where('email', 'student@university.edu')->firstOrFail();

        $notifications = [
            [
                'Enrollment Reminder',
                'Your enrollment deadline is August 30. Complete enrollment immediately to avoid being dropped.',
                'warning',
                'school',
                false,
                '/enrollment',
                null,
            ],
            [
                'Event Reminder: IT Summit 2025',
                'IT Summit you RSVPed for is tomorrow. Location: IT Auditorium. Bring student ID.',
                'info',
                'event',
                false,
                '/events/2',
                null,
            ],
            [
                'Grade Released: Mobile Computing',
                'Your IT321 midterm grade has been released. Check the student portal.',
                'success',
                'grade',
                true,
                '/grades',
                now()->subHours(2),
            ],
            [
                'Scholarship Application Open',
                'OSS accepts scholarship applications until September 15. Submit requirements to OSS Building B.',
                'info',
                'star',
                false,
                '/announcements/4',
                null,
            ],
            [
                'New Announcement: Foundation Week',
                'Foundation Week Activities have been announced. Tap to read.',
                'info',
                'announcement',
                false,
                '/announcements/3',
                null,
            ],
        ];

        foreach ($notifications as [$title, $msg, $type, $icon, $read, $url, $readAt]) {
            Notification::firstOrCreate(
                ['user_id' => $student->id, 'title' => $title],
                [
                    'user_id' => $student->id,
                    'title' => $title,
                    'message' => $msg,
                    'type' => $type,
                    'icon' => $icon,
                    'is_read' => $read,
                    'action_url' => $url,
                    'read_at' => $readAt,
                ]
            );
        }

        $this->command->info('Notifications seeded (5 records).');
    }
}
