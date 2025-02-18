<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardOperatorController extends Controller
{
    public function __invoke(): Response
    {
        return inertia('Operators/Dashboard', [
            'page_settings' => [
                'title' => 'Dashboard',
                'subtitle' => 'Menampilkan semua statistik pada platform ini.',
            ],
            'count' => [
                'students' => Student::query()
                    // ->where('faculty_id', auth()->user()->operator->faculty_id)
                    // ->where('department_id', auth()->user()->operator->department_id)
                    ->count(),
                'teachers' => Teacher::query()
                    // ->where('faculty_id', auth()->user()->operator->faculty_id)
                    // ->where('department_id', auth()->user()->operator->department_id)
                    ->count(),
                'classrooms' => Classroom::query()
                    // ->where('faculty_id', auth()->user()->operator->faculty_id)
                    // ->where('department_id', auth()->user()->operator->department_id)
                    ->count(),
                'courses' => Course::query()
                    // ->where('faculty_id', auth()->user()->operator->faculty_id)
                    // ->where('department_id', auth()->user()->operator->department_id)
                    ->count(),
            ],
        ]);
    }
}
