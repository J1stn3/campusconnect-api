<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Schedule::where('user_id', $user->id)->currentSemester();

        if ($request->has('day')) {
            $query->forDay(ucfirst(strtolower($request->query('day'))));
        }

        $schedules = $query
            ->orderByRaw("FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
            ->orderBy('time_start')
            ->get();

        $grouped = $schedules->groupBy('day');
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $weeklySchedule = collect($days)->map(fn ($day) => [
            'day' => $day,
            'classes' => $grouped->get($day, collect())->values(),
        ]);

        return $this->successResponse([
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'student_id' => $user->student_id,
            ],
            'weekly_schedule' => $weeklySchedule,
            'summary' => [
                'total_subjects' => $schedules->unique('subject_code')->count(),
                'total_units' => $schedules->unique('subject_code')->sum('units'),
            ],
        ], 'Schedule retrieved successfully.');
    }
}
