<?php

namespace App\Http\Controllers\Student;

use App\Enums\FeeStatus;
use App\Enums\StudyPlanStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Fee;
use App\Models\StudyPlan;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardStudentController extends Controller
{
    public function __invoke(): Response
    {
        // Fetch active announcements for students
        $announcements = Announcement::active()
            ->forStudents()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return inertia('Students/Dashboard', [
            'page_settings' => [
                'title' => 'Dashboard',
                'subtitle' => 'Menampilkan semua statistik pada platform ini.',
            ],
            'count' => [
                'study_plans_approved' => StudyPlan::query()
                    ->where('student_id', auth()->user()->student->id)
                    ->where('status', StudyPlanStatus::APPROVED->value)
                    ->count(),
                'study_plans_reject' => StudyPlan::query()
                    ->where('student_id', auth()->user()->student->id)
                    ->where('status', StudyPlanStatus::REJECT->value)
                    ->count(),
                'total_payments' => Fee::query()
                    ->where('student_id', auth()->user()->student->id)
                    ->where('status', FeeStatus::SUCCESS->value)
                    ->get()
                    ->sum(fn($fee) => $fee->feeGroup->amount),
            ],
            'announcements' => $announcements,
        ]);
    }
}
