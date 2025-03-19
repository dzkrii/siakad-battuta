<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardTeacherController extends Controller
{
    public function __invoke(): Response
    {
        // Fetch active announcements for teachers
        $announcements = Announcement::active()
            ->forTeachers()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return inertia('Teachers/Dashboard', [
            'page_settings' => [
                'title' => 'Dashboard',
                'subtitle' => 'Menampilkan semua statistik pada platform ini.',
            ],
            'count' => [
                'courses' => Course::query()
                    ->where('teacher_id', auth()->user()->teacher->id)
                    ->where('academic_year_id', activeAcademicYear()->id)
                    ->count(),
                'classrooms' => Classroom::query()
                    ->whereHas('schedules.course', fn($query) => $query->where('teacher_id', auth()->user()->teacher->id))
                    ->where('academic_year_id', activeAcademicYear()->id)
                    ->count(),
                'schedules' => Schedule::query()
                    ->whereHas('course', fn($query) => $query->where('teacher_id', auth()->user()->teacher->id))
                    ->where('academic_year_id', activeAcademicYear()->id)
                    ->count(),
            ],
            'announcements' => $announcements,
        ]);
    }
}
