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

    public function index(Request $request): JsonResponse
    {
        $query = Event::with('organizer:id,name,role');

        if ($request->has('category')) {
            $query->byCategory($request->query('category'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->boolean('free')) {
            $query->free();
        }

        $events = $query->orderBy('date')->get();
        $events->each(fn ($e) => $e->append(['rsvp_count', 'seats_available', 'is_full']));

        return $this->successResponse($events, 'Events retrieved.');
    }

    public function show(string $id): JsonResponse
    {
        $event = Event::with('organizer:id,name,role')->find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $event->append(['rsvp_count', 'seats_available', 'is_full']);

        return $this->successResponse($event, 'Event retrieved.');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time_start' => ['required', 'date_format:H:i:s,H:i'],
            'time_end' => ['required', 'date_format:H:i:s,H:i', 'after:time_start'],
            'category' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'is_free' => ['nullable', 'boolean'],
            'contact_email' => ['nullable', 'email'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:date'],
        ]);

        $event = Event::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'status' => 'upcoming',
            'is_free' => $validated['is_free'] ?? true,
        ]));

        $event->load('organizer:id,name,role');
        $event->append(['rsvp_count', 'seats_available', 'is_full']);

        return $this->createdResponse($event, 'Event created.');
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $v = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'location' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'time_start' => 'sometimes|date_format:H:i:s,H:i',
            'time_end' => 'sometimes|date_format:H:i:s,H:i',
            'category' => 'sometimes|string|max:100',
            'capacity' => 'sometimes|integer|min:1',
            'status' => 'sometimes|in:upcoming,ongoing,past,cancelled',
            'is_free' => 'sometimes|boolean',
        ]);

        $event->update($v);
        $event->load('organizer:id,name,role');
        $event->append(['rsvp_count', 'seats_available', 'is_full']);

        return $this->successResponse($event, 'Event updated.');
    }

    public function destroy(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $title = $event->title;
        $event->delete();

        return $this->successResponse(null, "\"{$title}\" deleted.");
    }

    public function rsvp(Request $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event');
        }

        $user = $request->user();

        // CHECK 1 — Duplicate RSVP
        $existing = EventRsvp::where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing) {
            return $this->errorResponse('You have already RSVPed to this event.', 409);
        }

        // CHECK 2 — Capacity
        if ($event->is_full) {
            return $this->errorResponse('This event is fully booked.', 409);
        }

        // CHECK 3 — Registration deadline (null check required)
        if ($event->registration_deadline && now()->gt($event->registration_deadline->endOfDay())) {
            return $this->errorResponse('Registration deadline has passed.', 422);
        }

        // All checks passed — create RSVP record
        $rsvp = EventRsvp::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => 'confirmed',
            'rsvp_at' => now(),
        ]);

        return $this->createdResponse([
            'rsvp_id' => $rsvp->id,
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_date' => $event->date->toDateString(),
            'event_location' => $event->location,
            'user_name' => $user->name,
            'status' => 'confirmed',
            'rsvp_at' => $rsvp->rsvp_at->toDateTimeString(),
        ], "RSVP confirmed for \"{$event->title}\".");
    }
}
