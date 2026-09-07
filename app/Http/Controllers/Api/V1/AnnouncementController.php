<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/v1/announcements
     * Return a list of all announcements.
     */
    public function index(): JsonResponse
    {
        // Temporary hardcoded data — we will replace this with
        // database queries in Module 10.
        $announcements = [
            [
                'id' => 1,
                'title' => 'Enrollment for Second Semester Now Open',
                'body' => 'Students may now enroll for the second semester via the student portal.',
                'category' => 'Academic',
                'posted_by' => 'Registrar Office',
                'posted_at' => '2025-08-01 08:00:00',
            ],
            [
                'id' => 2,
                'title' => 'Campus WiFi Maintenance — August 10',
                'body' => 'The campus WiFi will be unavailable from 12 AM to 4 AM on August 10.',
                'category' => 'IT Services',
                'posted_by' => 'IT Department',
                'posted_at' => '2025-08-05 09:00:00',
            ],
            [
                'id' => 3,
                'title' => 'Foundation Week Activities',
                'body' => 'Join us for a week of exciting activities celebrating our university foundation.',
                'category' => 'Events',
                'posted_by' => 'Student Affairs',
                'posted_at' => '2025-08-07 10:00:00',
            ],
        ];

        return $this->successResponse(
            $announcements,
            'Announcements retrieved successfully.',
            200
        );
    }

    /**
     * GET /api/v1/announcements/{id}
     * Return a single announcement.
     */
    public function show(string $id): JsonResponse
    {
        // Simulated find — will use Eloquent in Module 10
        $announcements = [
            1 => [
                'id' => 1,
                'title' => 'Enrollment for Second Semester Now Open',
                'body' => 'Students may now enroll for the second semester via the student portal.',
                'category' => 'Academic',
                'posted_by' => 'Registrar Office',
                'posted_at' => '2025-08-01 08:00:00',
            ],
        ];

        if (! isset($announcements[(int) $id])) {
            return $this->notFoundResponse('Announcement');
        }

        return $this->successResponse(
            $announcements[(int) $id],
            'Announcement retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/announcements
     * Store a new announcement.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|string|max:100',
        ]);

        // Simulate saving — will persist to DB in Module 10
        $newAnnouncement = array_merge(['id' => 4], $validated, [
            'posted_by' => 'Demo User',
            'posted_at' => now()->toDateTimeString(),
        ]);

        return $this->successResponse(
            $newAnnouncement,
            'Announcement created successfully.',
            201
        );
    }

    /**
     * PUT/PATCH /api/v1/announcements/{id}
     * Update an announcement.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|max:100',
        ]);

        $updated = array_merge([
            'id' => (int) $id,
            'title' => 'Enrollment for Second Semester Now Open',
            'body' => 'Students may now enroll for the second semester via the student portal.',
            'category' => 'Academic',
            'posted_by' => 'Registrar Office',
            'posted_at' => '2025-08-01 08:00:00',
        ], $validated);

        return $this->successResponse(
            $updated,
            'Announcement updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/announcements/{id}
     * Delete an announcement.
     */
    public function destroy(string $id): JsonResponse
    {
        // Simulate deletion
        return $this->successResponse(
            null,
            "Announcement #{$id} deleted successfully.",
            200
        );
    }
}
