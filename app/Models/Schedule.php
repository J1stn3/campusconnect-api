<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_code',
        'subject_name',
        'instructor',
        'room',
        'day',
        'time_start',
        'time_end',
        'units',
        'section',
        'type',
        'semester',
        'school_year',
    ];

    protected $casts = [
        'units' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCurrentSemester($query)
    {
        return $query->where('school_year', '2025-2026')
            ->where('semester', '1st');
    }

    public function scopeForDay($query, string $day)
    {
        return $query->where('day', $day);
    }
}
