<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (DB::getDriverName() === 'sqlite') {
            $query->orderByRaw("CASE day 
                WHEN 'Monday' THEN 1 
                WHEN 'Tuesday' THEN 2 
                WHEN 'Wednesday' THEN 3 
                WHEN 'Thursday' THEN 4 
                WHEN 'Friday' THEN 5 
                WHEN 'Saturday' THEN 6 
                WHEN 'Sunday' THEN 7 
                ELSE 8 END");
        } else {
            $query->orderByRaw("FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
        }

        $schedules = $query->orderBy('time_start')->get();

        $grouped = $schedules->groupBy('day');
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $weekly = collect($days)->map(fn ($d) => [
            'day' => $d,
            'classes' => $grouped->get($d, collect())->values(),
        ]);
        $unique = $schedules->unique('subject_code');

        return $this->successResponse([
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'student_id' => $user->student_id,
            ],
            'weekly_schedule' => $weekly,
            'summary' => [
                'total_subjects' => $unique->count(),
                'total_units' => $unique->sum('units'),
                'school_year' => '2025-2026',
                'semester' => '1st',
            ],
        ], 'Schedule retrieved.');
    }
}
