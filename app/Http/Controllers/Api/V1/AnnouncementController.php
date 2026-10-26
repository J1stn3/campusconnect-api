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

        $announcements = $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($announcements, 'Announcements retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['nullable', 'in:low,medium,high'],
            'is_pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $announcement = Announcement::create([
            'user_id' => $request->user()->id, // ALWAYS from token — never from body
            'title' => $validated['title'],
            'body' => $validated['body'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 'medium',
            'is_pinned' => $validated['is_pinned'] ?? false,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $announcement->load('user:id,name,role');

        return $this->createdResponse($announcement, 'Announcement created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $a = Announcement::with('user:id,name,role')->find($id);

        if (! $a) {
            return $this->notFoundResponse('Announcement');
        }

        return $this->successResponse($a, 'Announcement retrieved.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $a = Announcement::find($id);

        if (! $a) {
            return $this->notFoundResponse('Announcement');
        }

        $v = $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
            'priority' => 'sometimes|in:low,medium,high',
            'is_pinned' => 'sometimes|boolean',
            'expires_at' => 'sometimes|nullable|date',
        ]);

        $a->update($v);
        $a->load('user:id,name,role');

        return $this->successResponse($a, 'Announcement updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $a = Announcement::find($id);

        if (! $a) {
            return $this->notFoundResponse('Announcement');
        }

        $title = $a->title;
        $a->delete();

        return $this->successResponse(null, "\"{$title}\" deleted.");
    }
}
