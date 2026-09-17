<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        $events = Event::with('organizer:id,name,role')
            ->orderBy('date')
            ->get();

        return $this->successResponse($events, 'Events retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'date' => 'required|date',
            'time_start' => 'required',
            'time_end' => 'required',
            'category' => 'required|string|max:100',
            'status' => 'nullable|in:upcoming,ongoing,past,cancelled',
            'capacity' => 'nullable|integer|min:1',
            'is_free' => 'nullable|boolean',
            'contact_email' => 'nullable|email',
            'image_url' => 'nullable|string',
            'registration_deadline' => 'nullable|date',
        ]);

        $event = Event::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        $event->load('organizer:id,name,role');

        return $this->createdResponse($event, 'Event created successfully.');
    }

    public function show(string $id): JsonResponse
    {
        $event = Event::with(['organizer:id,name,role', 'attendees:id,name,role'])->find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        return $this->successResponse($event, 'Event retrieved successfully.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'time_start' => 'sometimes',
            'time_end' => 'sometimes',
            'category' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:upcoming,ongoing,past,cancelled',
            'capacity' => 'sometimes|integer|min:1',
            'is_free' => 'sometimes|boolean',
            'contact_email' => 'sometimes|nullable|email',
            'image_url' => 'sometimes|nullable|string',
            'registration_deadline' => 'sometimes|nullable|date',
        ]);

        $event->update($validated);
        $event->load('organizer:id,name,role');

        return $this->successResponse($event, 'Event updated successfully.');
    }

    public function destroy(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $title = $event->title;
        $event->delete();

        return $this->successResponse(null, "\"{$title}\" deleted successfully.");
    }

    public function rsvp(Request $request, string $event): JsonResponse
    {
        $eventModel = Event::find($event);

        if (! $eventModel) {
            return $this->notFoundResponse('Event');
        }

        $user = $request->user();

        $existing = EventRsvp::where('user_id', $user->id)
            ->where('event_id', $eventModel->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('Already RSVPed to this event.', 409);
        }

        if ($eventModel->is_full) {
            return $this->errorResponse('Event is fully booked.', 409);
        }

        $rsvp = EventRsvp::create([
            'user_id' => $user->id,
            'event_id' => $eventModel->id,
            'status' => 'confirmed',
            'rsvp_at' => now(),
        ]);

        return $this->createdResponse([
            'rsvp_id' => $rsvp->id,
            'event_title' => $eventModel->title,
            'event_date' => $eventModel->date->toDateString(),
            'user_name' => $user->name,
            'status' => 'confirmed',
        ], "RSVP confirmed for \"{$eventModel->title}\".");
    }
}
