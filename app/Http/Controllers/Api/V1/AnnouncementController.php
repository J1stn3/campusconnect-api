<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $query = Announcement::with('user:id,name,role')->active();

        if ($request->has('category')) {
            $query->byCategory($request->query('category'));
        }

        if ($request->boolean('pinned')) {
            $query->pinned();
        }

        $announcements = $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($announcements, 'Announcements retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category' => 'required|string|max:100',
            'priority' => 'nullable|in:low,medium,high',
            'is_pinned' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $announcement = Announcement::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $announcement->load('user:id,name,role');

        return $this->createdResponse($announcement, 'Announcement created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $announcement = Announcement::with('user:id,name,role')->find($id);

        if (! $announcement) {
            return $this->notFoundResponse('Announcement');
        }

        return $this->successResponse($announcement, 'Announcement retrieved successfully.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $announcement = Announcement::find($id);

        if (! $announcement) {
            return $this->notFoundResponse('Announcement');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'priority' => 'sometimes|in:low,medium,high',
            'is_pinned' => 'sometimes|boolean',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        $announcement->update($validated);
        $announcement->load('user:id,name,role');

        return $this->successResponse($announcement, 'Announcement updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $announcement = Announcement::find($id);

        if (! $announcement) {
            return $this->notFoundResponse('Announcement');
        }

        $title = $announcement->title;
        $announcement->delete();

        return $this->successResponse(null, "\"{$title}\" deleted successfully.");
    }
}
