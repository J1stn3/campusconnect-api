<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ChatbotMessage;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Module12RestApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $faculty;
    private User $student;
    private string $adminToken;
    private string $facultyToken;
    private string $studentToken;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('sanctum.guard', []);

        // Seed users matching the course seeders
        $this->admin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@university.edu',
            'password' => Hash::make('admin@2025'),
            'role' => 'admin',
        ]);

        $this->faculty = User::factory()->create([
            'name' => 'Prof. Maria Santos',
            'email' => 'msantos@university.edu',
            'password' => Hash::make('faculty@2025'),
            'role' => 'faculty',
        ]);

        $this->student = User::factory()->create([
            'name' => 'Juan dela Cruz',
            'email' => 'student@university.edu',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'student_id' => '2021-00123',
        ]);

        // Login to retrieve Bearer tokens
        $this->adminToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@university.edu',
            'password' => 'admin@2025',
        ])->json('data.token');

        $this->facultyToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'msantos@university.edu',
            'password' => 'faculty@2025',
        ])->json('data.token');

        $this->studentToken = $this->postJson('/api/v1/auth/login', [
            'email' => 'student@university.edu',
            'password' => 'password123',
        ])->json('data.token');
    }

    private function withBearerToken(string $token): static
    {
        $this->app['auth']->forgetGuards();
        return $this->withToken($token);
    }

    /**
     * CHECKPOINT A: All 9 Announcement Tests
     */
    public function test_checkpoint_a_announcements_crud_and_permissions(): void
    {
        // Seed 5 announcements (matching AnnouncementSeeder)
        $seeded = [
            ['title' => 'Announcement 1', 'body' => 'Body 1', 'category' => 'Academic', 'priority' => 'high', 'is_pinned' => true],
            ['title' => 'Announcement 2', 'body' => 'Body 2', 'category' => 'IT Services', 'priority' => 'medium', 'is_pinned' => false],
            ['title' => 'Announcement 3', 'body' => 'Body 3', 'category' => 'Events', 'priority' => 'medium', 'is_pinned' => true],
            ['title' => 'Announcement 4', 'body' => 'Body 4', 'category' => 'Financial Aid', 'priority' => 'high', 'is_pinned' => false],
            ['title' => 'Announcement 5', 'body' => 'Body 5', 'category' => 'Academic', 'priority' => 'low', 'is_pinned' => false],
        ];

        foreach ($seeded as $item) {
            Announcement::create(array_merge($item, [
                'user_id' => $this->admin->id,
                'expires_at' => now()->addDays(30),
            ]));
        }

        // 1. GET /announcements (student) -> 200 + 5 seeded announcements with user object
        $res1 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/announcements');
        $res1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'body', 'user' => ['id', 'name', 'role']]]]);

        // 2. GET /announcements?category=Academic (student) -> 200 + filtered list
        $res2 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/announcements?category=Academic');
        $res2->assertOk()->assertJsonCount(2, 'data');

        // 3. GET /announcements?pinned=true (student) -> 200 + only pinned
        $res3 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/announcements?pinned=true');
        $res3->assertOk()->assertJsonCount(2, 'data');

        // 4. POST /announcements (student) -> 403 (EnsureIsAdmin blocks student)
        $postData = [
            'title' => 'Module 12 Test Announcement',
            'body' => 'Created during activity.',
            'category' => 'Academic',
            'priority' => 'medium',
            'is_pinned' => false,
        ];
        $res4 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/announcements', $postData);
        $res4->assertForbidden();

        // 5. POST /announcements (admin) -> 201 (save ID)
        $res5 = $this->withBearerToken($this->adminToken)->postJson('/api/v1/announcements', $postData);
        $res5->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Module 12 Test Announcement')
            ->assertJsonPath('data.user_id', $this->admin->id)
            ->assertJsonStructure(['data' => ['id', 'user' => ['id', 'name', 'role']]]);
        $announcementId = $res5->json('data.id');

        // 6. GET /announcements/{id} (student) -> 200
        $res6 = $this->withBearerToken($this->studentToken)->getJson("/api/v1/announcements/{$announcementId}");
        $res6->assertOk()->assertJsonPath('data.title', 'Module 12 Test Announcement');

        // 7. PUT /announcements/{id} (admin) -> 200 (updated title)
        $res7 = $this->withBearerToken($this->adminToken)->putJson("/api/v1/announcements/{$announcementId}", [
            'title' => 'Updated Module 12 Title',
        ]);
        $res7->assertOk()->assertJsonPath('data.title', 'Updated Module 12 Title');

        // 8. DELETE /announcements/{id} (admin) -> 200 (deleted from DB)
        $res8 = $this->withBearerToken($this->adminToken)->deleteJson("/api/v1/announcements/{$announcementId}");
        $res8->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseMissing('announcements', ['id' => $announcementId]);

        // 9. GET /announcements/{id} (student) -> 404 (confirms deletion)
        $res9 = $this->withBearerToken($this->studentToken)->getJson("/api/v1/announcements/{$announcementId}");
        $res9->assertNotFound()->assertJsonPath('success', false);
    }

    /**
     * CHECKPOINT B: All 8 Event & RSVP Tests + 3-Check RSVP
     */
    public function test_checkpoint_b_events_crud_and_rsvp_checks(): void
    {
        // Seed 3 events matching EventSeeder
        $event1 = Event::create([
            'user_id' => $this->admin->id,
            'title' => 'University Foundation Week 2025',
            'description' => 'Annual celebration.',
            'location' => 'Main Campus Grounds',
            'date' => now()->addDays(5)->toDateString(),
            'time_start' => '08:00:00',
            'time_end' => '22:00:00',
            'category' => 'University Event',
            'status' => 'upcoming',
            'capacity' => 2000,
            'is_free' => true,
            'registration_deadline' => now()->addDays(3)->toDateString(),
        ]);

        $event2 = Event::create([
            'user_id' => $this->faculty->id,
            'title' => 'IT Summit 2025: AI in the Real World',
            'description' => 'Keynote speakers.',
            'location' => 'College of IT Auditorium',
            'date' => now()->addDays(10)->toDateString(),
            'time_start' => '08:00:00',
            'time_end' => '17:00:00',
            'category' => 'Academic',
            'status' => 'upcoming',
            'capacity' => 1, // capacity 1 for testing Check 2
            'is_free' => true,
            'registration_deadline' => now()->addDays(7)->toDateString(),
        ]);

        $event3 = Event::create([
            'user_id' => $this->admin->id,
            'title' => 'Campus Job Fair 2025',
            'description' => 'Recruiters from 50+ companies.',
            'location' => 'Student Center',
            'date' => now()->addDays(15)->toDateString(),
            'time_start' => '09:00:00',
            'time_end' => '16:00:00',
            'category' => 'Career',
            'status' => 'upcoming',
            'capacity' => 500,
            'is_free' => true,
            'registration_deadline' => now()->subDay()->toDateString(), // passed deadline for testing Check 3
        ]);

        // 1. GET /events (student) -> 200 + 3 events with rsvp_count, seats_available, is_full
        $res1 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/events');
        $res1->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'rsvp_count', 'seats_available', 'is_full']]]);

        // 2. POST /events (student) -> 403
        $newEventData = [
            'title' => 'Module 12 Test Event',
            'description' => 'Test event.',
            'location' => 'IT Auditorium',
            'date' => now()->addMonth()->toDateString(),
            'time_start' => '09:00:00',
            'time_end' => '17:00:00',
            'category' => 'Academic',
            'capacity' => 50,
        ];
        $res2 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/events', $newEventData);
        $res2->assertForbidden();

        // 3. POST /events (faculty) -> 201 (save event_id)
        $res3 = $this->withBearerToken($this->facultyToken)->postJson('/api/v1/events', $newEventData);
        $res3->assertCreated()
            ->assertJsonPath('data.title', 'Module 12 Test Event')
            ->assertJsonStructure(['data' => ['id', 'rsvp_count', 'seats_available', 'is_full']]);
        $eventId = $res3->json('data.id');

        // 4. GET /events/{id} (student) -> 200 (single event with computed fields)
        $res4 = $this->withBearerToken($this->studentToken)->getJson("/api/v1/events/{$eventId}");
        $res4->assertOk()
            ->assertJsonPath('data.id', $eventId)
            ->assertJsonStructure(['data' => ['rsvp_count', 'seats_available', 'is_full']]);

        // 5. POST /events/{id}/rsvp (student) -> 201 (RSVP confirmed)
        $res5 = $this->withBearerToken($this->studentToken)->postJson("/api/v1/events/{$event1->id}/rsvp");
        $res5->assertCreated()
            ->assertJsonPath('data.event_id', $event1->id)
            ->assertJsonPath('data.status', 'confirmed');
        $this->assertDatabaseHas('event_rsvps', [
            'user_id' => $this->student->id,
            'event_id' => $event1->id,
        ]);

        // 6. POST /events/{id}/rsvp again (student) -> 409 (CHECK 1: already RSVPed)
        $res6 = $this->withBearerToken($this->studentToken)->postJson("/api/v1/events/{$event1->id}/rsvp");
        $res6->assertStatus(409)
            ->assertJsonPath('message', 'You have already RSVPed to this event.');

        // CHECK 2: Capacity check (409 Conflict)
        // Fill event2 with another user's RSVP
        EventRsvp::create([
            'user_id' => $this->admin->id,
            'event_id' => $event2->id,
            'status' => 'confirmed',
            'rsvp_at' => now(),
        ]);
        $resCapacity = $this->withBearerToken($this->studentToken)->postJson("/api/v1/events/{$event2->id}/rsvp");
        $resCapacity->assertStatus(409)
            ->assertJsonPath('message', 'This event is fully booked.');

        // CHECK 3: Registration deadline passed (422 Unprocessable)
        $resDeadline = $this->withBearerToken($this->studentToken)->postJson("/api/v1/events/{$event3->id}/rsvp");
        $resDeadline->assertStatus(422)
            ->assertJsonPath('message', 'Registration deadline has passed.');

        // 7. PUT /events/{id} (faculty) -> 200 (updated)
        $res7 = $this->withBearerToken($this->facultyToken)->putJson("/api/v1/events/{$eventId}", [
            'title' => 'Updated Module 12 Event Title',
        ]);
        $res7->assertOk()->assertJsonPath('data.title', 'Updated Module 12 Event Title');

        // 8. DELETE /events/{id} (faculty) -> 200 (deleted)
        $res8 = $this->withBearerToken($this->facultyToken)->deleteJson("/api/v1/events/{$eventId}");
        $res8->assertOk();
        $this->assertDatabaseMissing('events', ['id' => $eventId]);
    }

    /**
     * CHECKPOINT C: Schedule Controller
     */
    public function test_checkpoint_c_schedule_isolation_and_order(): void
    {
        // Seed student schedule
        Schedule::create([
            'user_id' => $this->student->id,
            'subject_code' => 'IT321',
            'subject_name' => 'Mobile Computing',
            'instructor' => 'Prof. Maria Santos',
            'room' => 'IT Lab 3',
            'day' => 'Monday',
            'time_start' => '07:30:00',
            'time_end' => '09:00:00',
            'type' => 'Lecture/Laboratory',
            'units' => 3,
            'section' => 'BSIT-3A',
            'semester' => '1st',
            'school_year' => '2025-2026',
        ]);

        Schedule::create([
            'user_id' => $this->student->id,
            'subject_code' => 'IT323',
            'subject_name' => 'Information Assurance',
            'instructor' => 'Prof. Carlos Mendoza',
            'room' => 'Room 305',
            'day' => 'Tuesday',
            'time_start' => '10:30:00',
            'time_end' => '12:00:00',
            'type' => 'Lecture',
            'units' => 3,
            'section' => 'BSIT-3A',
            'semester' => '1st',
            'school_year' => '2025-2026',
        ]);

        // 1. GET /schedules (student) -> 200, 7 day buckets, Monday first
        $res1 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/schedules');
        $res1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(7, 'data.weekly_schedule')
            ->assertJsonPath('data.weekly_schedule.0.day', 'Monday')
            ->assertJsonPath('data.weekly_schedule.1.day', 'Tuesday')
            ->assertJsonPath('data.weekly_schedule.6.day', 'Sunday')
            ->assertJsonPath('data.student.student_id', '2021-00123')
            ->assertJsonPath('data.summary.total_subjects', 2)
            ->assertJsonPath('data.summary.total_units', 6);

        // 2. GET /schedules?day=Monday (student) -> 200, only Monday classes in weekly schedule
        $res2 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/schedules?day=Monday');
        $res2->assertOk()
            ->assertJsonCount(1, 'data.weekly_schedule.0.classes')
            ->assertJsonCount(0, 'data.weekly_schedule.1.classes');

        // 3. GET /schedules (admin) -> 200, 7 empty buckets (admin has no schedule)
        $res3 = $this->withBearerToken($this->adminToken)->getJson('/api/v1/schedules');
        $res3->assertOk()
            ->assertJsonCount(7, 'data.weekly_schedule')
            ->assertJsonCount(0, 'data.weekly_schedule.0.classes')
            ->assertJsonPath('data.summary.total_subjects', 0);
    }

    /**
     * CHECKPOINT D: Notification Controller & IDOR Protection
     */
    public function test_checkpoint_d_notifications_and_idor_protection(): void
    {
        // Seed notifications for student
        $notif1 = Notification::create([
            'user_id' => $this->student->id,
            'title' => 'Enrollment Reminder',
            'message' => 'Deadline is August 30.',
            'type' => 'warning',
            'icon' => 'school',
            'is_read' => false,
            'action_url' => '/enrollment',
        ]);

        $notif2 = Notification::create([
            'user_id' => $this->student->id,
            'title' => 'Event Reminder',
            'message' => 'Event tomorrow.',
            'type' => 'info',
            'icon' => 'event',
            'is_read' => false,
            'action_url' => '/events/2',
        ]);

        $notif3 = Notification::create([
            'user_id' => $this->student->id,
            'title' => 'Grade Released',
            'message' => 'Grade posted.',
            'type' => 'success',
            'icon' => 'grade',
            'is_read' => true,
            'action_url' => '/grades',
            'read_at' => now()->subHour(),
        ]);

        // 1. GET /notifications -> 200, unread_count=2, total=3
        $res1 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/notifications');
        $res1->assertOk()
            ->assertJsonPath('data.unread_count', 2)
            ->assertJsonCount(3, 'data.notifications');

        // 2. GET /notifications?unread=true -> 200, only unread
        $res2 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/notifications?unread=true');
        $res2->assertOk()->assertJsonCount(2, 'data.notifications');

        // 3. GET /notifications?type=warning -> 200, only warning type
        $res3 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/notifications?type=warning');
        $res3->assertOk()->assertJsonCount(1, 'data.notifications');

        // 4. PATCH /notifications/{id}/read -> 200, is_read=true, read_at set
        $res4 = $this->withBearerToken($this->studentToken)->patchJson("/api/v1/notifications/{$notif1->id}/read");
        $res4->assertOk()
            ->assertJsonPath('data.is_read', true);
        $this->assertNotNull($res4->json('data.read_at'));

        // 5. GET /notifications?unread=true -> unread_count decreased by 1
        $res5 = $this->withBearerToken($this->studentToken)->getJson('/api/v1/notifications');
        $res5->assertOk()->assertJsonPath('data.unread_count', 1);

        // 6. DELETE /notifications/{id} -> 200 deleted
        $res6 = $this->withBearerToken($this->studentToken)->deleteJson("/api/v1/notifications/{$notif1->id}");
        $res6->assertOk();
        $this->assertDatabaseMissing('notifications', ['id' => $notif1->id]);

        // 7. GET /notifications/{id} -> 404 confirms deletion
        $res7 = $this->withBearerToken($this->studentToken)->getJson("/api/v1/notifications/{$notif1->id}");
        $res7->assertNotFound();

        // 8. IDOR Security Test: Admin cannot access student's notification
        $resIdor = $this->withBearerToken($this->adminToken)->getJson("/api/v1/notifications/{$notif2->id}");
        $resIdor->assertNotFound(); // Must return 404 Not Found to prevent IDOR
    }

    /**
     * CHECKPOINT E: Chatbot Controller & DB Persistence
     */
    public function test_checkpoint_e_chatbot_db_persistence(): void
    {
        // 1. POST /chatbot {"message":"Hello"}
        $res1 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/chatbot', [
            'message' => 'Hello',
        ]);
        $res1->assertOk()
            ->assertJsonPath('data.bot_name', 'Campus')
            ->assertJsonStructure(['data' => ['id', 'conversation_id', 'bot_response', 'suggestions']]);
        $this->assertStringContainsString('Hello! I am Campus', $res1->json('data.bot_response'));

        // 2. POST /chatbot {"message":"When is enrollment?"}
        $res2 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/chatbot', [
            'message' => 'When is enrollment?',
        ]);
        $res2->assertOk();
        $this->assertStringContainsString('Enrollment for 1st Semester is open', $res2->json('data.bot_response'));

        // 3. POST /chatbot {"message":"Tell me about events"}
        $res3 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/chatbot', [
            'message' => 'Tell me about events',
        ]);
        $res3->assertOk();
        $this->assertStringContainsString('Upcoming events are in the Events section', $res3->json('data.bot_response'));

        // 4. GET /chatbot/history -> 200, returns 3 messages just sent
        $resHistory = $this->withBearerToken($this->studentToken)->getJson('/api/v1/chatbot/history');
        $resHistory->assertOk()
            ->assertJsonCount(3, 'data');

        // 5. Test conversation grouping with conversation_id
        $resConv1 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/chatbot', [
            'message' => 'What is the enrollment deadline?',
            'conversation_id' => 'test_001',
        ]);
        $resConv1->assertOk()->assertJsonPath('data.conversation_id', 'test_001');

        $resConv2 = $this->withBearerToken($this->studentToken)->postJson('/api/v1/chatbot', [
            'message' => 'Tell me about library books',
            'conversation_id' => 'test_001',
        ]);
        $resConv2->assertOk()->assertJsonPath('data.conversation_id', 'test_001');

        $this->assertSame(2, ChatbotMessage::where('conversation_id', 'test_001')->count());
    }
}
