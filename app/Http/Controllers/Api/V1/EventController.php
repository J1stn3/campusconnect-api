<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        return $this->successResponse([], 'Events retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        return $this->successResponse(null, 'Event store placeholder.', 201);
    }

    public function show(string $id): JsonResponse
    {
        return $this->successResponse(['id' => (int) $id], 'Event retrieved successfully.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return $this->successResponse(['id' => (int) $id], 'Event updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        return $this->successResponse(null, "Event #{$id} deleted successfully.");
    }

    public function rsvp(Request $request, string $event): JsonResponse
    {
        return $this->successResponse(
            ['event_id' => (int) $event],
            'RSVP placeholder. Coming in a later module.'
        );
    }
}
