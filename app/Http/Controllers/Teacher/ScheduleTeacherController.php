<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\ScheduleDay;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Response;

class ScheduleTeacherController extends Controller
{
    public function __invoke(): Response
    {
        $courses = Course::query()
            ->where('teacher_id', auth()->user()->teacher->id)
            ->pluck('id');

        $schedules = Schedule::query()
            ->whereIn('course_id', $courses)
            ->get();

        $days = ScheduleDay::cases();
        $scheduleTable = [];

        foreach ($schedules as $schedule) {
            $startTime = substr($schedule->start_time, 0, 5);
            $endTime = substr($schedule->end_time, 0, 5);
            $day = $schedule->day_of_week->value;

            $scheduleTable[$startTime][$day] = [
                'course' => $schedule->course->name,
                'end_time' => $endTime,
            ];
        }

        $scheduleTable = collect($scheduleTable)->sortKeys();

        return inertia('Teachers/Schedules/Index', [
            'page_settings' => [
                'title' => 'Jadwal',
                'subtitle' => 'Menampilkan semua jadwal mengajar anda.',
            ],
            'scheduleTable' => $scheduleTable,
            'days' => $days,
        ]);
    }
}
