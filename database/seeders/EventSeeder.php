<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\User;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $faculty = User::where('role', 'faculty')->first();
        $students = User::where('role', 'student')->get();

        $eventsData = [
            [
                'user_id' => $admin->id,
                'title' => 'University Foundation Week 2025',
                'description' => 'Annual celebration featuring sports, cultural performances, and alumni homecoming.',
                'location' => 'Main Campus Grounds',
                'date' => '2026-10-18',
                'time_start' => '08:00:00',
                'time_end' => '22:00:00',
                'category' => 'University Event',
                'status' => 'upcoming',
                'capacity' => 2000,
                'is_free' => true,
                'registration_deadline' => '2026-10-15',
                'contact_email' => 'sao@university.edu',
            ],
            [
                'user_id' => $faculty->id,
                'title' => 'IT Summit 2025: AI in the Real World',
                'description' => 'Annual IT Summit with industry keynote speakers, panel discussions, live demos.',
                'location' => 'College of IT Auditorium',
                'date' => '2026-11-22',
                'time_start' => '08:00:00',
                'time_end' => '17:00:00',
                'category' => 'Academic',
                'status' => 'upcoming',
                'capacity' => 300,
                'is_free' => true,
                'registration_deadline' => '2026-11-20',
                'contact_email' => 'it.dept@university.edu',
            ],
            [
                'user_id' => $admin->id,
                'title' => 'Campus Job Fair 2025',
                'description' => 'Connect with recruiters from 50+ companies. Bring your resume for on-the-spot interviews.',
                'location' => 'Student Center',
                'date' => '2026-12-05',
                'time_start' => '09:00:00',
                'time_end' => '16:00:00',
                'category' => 'Career',
                'status' => 'upcoming',
                'capacity' => 500,
                'is_free' => true,
                'registration_deadline' => '2026-12-03',
                'contact_email' => 'careers@university.edu',
            ],
        ];

        foreach ($eventsData as $data) {
            $event = Event::firstOrCreate(['title' => $data['title']], $data);

            if ($event->wasRecentlyCreated && str_contains($event->title, 'Foundation')) {
                foreach ($students as $student) {
                    // Leave student@university.edu free so Postman RSVP demo works
                    if ($student->email === 'student@university.edu') {
                        continue;
                    }

                    EventRsvp::firstOrCreate(
                        ['user_id' => $student->id, 'event_id' => $event->id],
                        ['status' => 'confirmed', 'rsvp_at' => now()]
                    );
                }
            }
        }

        $this->command->info('Events seeded with sample RSVPs.');
    }
}
