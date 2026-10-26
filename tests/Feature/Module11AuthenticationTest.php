<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class Module11AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // This API test uses bearer tokens only; disabling the stateful web
        // guard prevents one simulated request from leaking into the next.
        config()->set('sanctum.guard', []);
    }

    public function test_complete_module_11_authentication_flow(): void
    {
        $admin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@university.edu',
            'password' => Hash::make('admin@2025'),
            'role' => 'admin',
        ]);

        $faculty = User::factory()->create([
            'name' => 'Prof. Maria Santos',
            'email' => 'msantos@university.edu',
            'password' => Hash::make('faculty@2025'),
            'role' => 'faculty',
        ]);

        // 1. Register a new user and receive a token.
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Student',
            'email' => 'test@university.edu',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'student_id' => '2025-00001',
            'role' => 'student',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonMissingPath('data.user.password');

        $student = User::where('email', 'test@university.edu')->firstOrFail();
        $this->assertTrue(Hash::check('SecurePass123!', $student->password));
        $this->assertNotSame('SecurePass123!', $student->password);
        $studentAccessToken = $student->tokens()->sole();
        $this->assertContains('events:rsvp', $studentAccessToken->abilities);
        $this->assertNotContains('events:write', $studentAccessToken->abilities);
        $this->assertNotNull($studentAccessToken->expires_at);

        // 2. Duplicate email registration is rejected.
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Another Student',
            'email' => 'test@university.edu',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        // 3. Login succeeds and replaces the registration token.
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@university.edu',
            'password' => 'SecurePass123!',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('data.user.email', 'test@university.edu')
            ->assertJsonPath('data.token_type', 'Bearer');

        $studentToken = $loginResponse->json('data.token');
        $this->assertSame(1, $student->tokens()->count());

        // 4. Wrong passwords and unknown emails use the same 401 response.
        $wrongPasswordResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@university.edu',
            'password' => 'wrongpassword',
        ])->assertUnauthorized();

        $unknownEmailResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@university.edu',
            'password' => 'SecurePass123!',
        ])->assertUnauthorized();

        $this->assertSame(
            $wrongPasswordResponse->json('message'),
            $unknownEmailResponse->json('message')
        );

        // 5. The token retrieves a password-free profile.
        $this->withBearerToken($studentToken)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.email', 'test@university.edu')
            ->assertJsonMissingPath('data.password');

        // 6. Profile changes persist.
        $this->withBearerToken($studentToken)
            ->putJson('/api/v1/auth/profile', ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'email' => 'test@university.edu',
            'name' => 'Updated Name',
        ]);

        // 7. Admin login.
        $adminToken = $this->postJson('/api/v1/auth/login', [
            'email' => $admin->email,
            'password' => 'admin@2025',
        ])->assertOk()->json('data.token');
        $this->assertSame(['*'], $admin->tokens()->sole()->abilities);

        // 8. Students cannot create announcements.
        $announcementPayload = [
            'title' => 'Module 11 Announcement',
            'body' => 'Authentication API is complete.',
            'category' => 'Academic',
            'priority' => 'high',
        ];

        $this->withBearerToken($studentToken)
            ->postJson('/api/v1/announcements', $announcementPayload)
            ->assertForbidden()
            ->assertJsonPath('success', false);

        // 9. Admins can create announcements.
        $this->withBearerToken($adminToken)
            ->postJson('/api/v1/announcements', $announcementPayload)
            ->assertCreated();

        // 10. Faculty login.
        $facultyToken = $this->postJson('/api/v1/auth/login', [
            'email' => $faculty->email,
            'password' => 'faculty@2025',
        ])->assertOk()->json('data.token');
        $this->assertContains('events:write', $faculty->tokens()->sole()->abilities);
        $this->assertNotContains('announcements:write', $faculty->tokens()->sole()->abilities);

        $eventPayload = [
            'title' => 'Mobile Computing Seminar',
            'description' => 'A seminar for CampusConnect students.',
            'location' => 'Main Auditorium',
            'date' => now()->addWeek()->toDateString(),
            'time_start' => '09:00',
            'time_end' => '11:00',
            'category' => 'Seminar',
            'capacity' => 100,
        ];

        // 11. Faculty can create events.
        $this->withBearerToken($facultyToken)
            ->postJson('/api/v1/events', $eventPayload)
            ->assertCreated();

        // 12. Students cannot create events.
        $this->withBearerToken($studentToken)
            ->postJson('/api/v1/events', $eventPayload)
            ->assertForbidden();

        // 13. Single-device logout revokes the current student token.
        $this->withBearerToken($studentToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // 14. A revoked token can no longer retrieve the profile.
        $this->withBearerToken($studentToken)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();

        // 15. Logout-all reports and removes every admin session.
        $admin->createToken('second-device');
        $this->assertSame(2, $admin->tokens()->count());

        $this->withBearerToken($adminToken)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out from 2 device(s) successfully.');

        $this->assertSame(0, $admin->tokens()->count());
        $this->assertSame(1, PersonalAccessToken::where('tokenable_id', $faculty->id)->count());
    }

    private function withBearerToken(string $token): static
    {
        // RequestGuard caches its resolved user during one application lifecycle.
        // Feature tests simulate many requests in that lifecycle, so reset it.
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }
}
