<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'location',
        'date',
        'time_start',
        'time_end',
        'category',
        'status',
        'capacity',
        'is_free',
        'contact_email',
        'image_url',
        'registration_deadline',
    ];

    protected $casts = [
        'date' => 'date',
        'registration_deadline' => 'date',
        'is_free' => 'boolean',
        'capacity' => 'integer',
    ];

    protected $appends = [
        'seats_available',
        'is_full',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_rsvps')
            ->withPivot('status', 'rsvp_at')
            ->withTimestamps();
    }

    public function getSeatsAvailableAttribute(): int
    {
        return max(0, $this->capacity - $this->rsvps()->count());
    }

    public function getIsFullAttribute(): bool
    {
        return $this->rsvps()->count() >= $this->capacity;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('date', '>=', now()->toDateString());
    }
}
