<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\StudentResource;
use App\Models\Student;
use App\Models\StudyPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Throwable;

class SemesterManagementController extends Controller
{
    /**
     * Display a listing of students grouped by semester
     */
    public function index(): Response
    {
        // Get unique semesters that have approved study plans
        $semestersWithApprovedPlans = StudyPlan::where('status', 'approved')
            ->select('semester')
            ->distinct()
            ->orderBy('semester')
            ->pluck('semester')
            ->toArray();

        // Get students with approved plans for each semester
        $studentsWithApprovedPlans = [];

        foreach ($semestersWithApprovedPlans as $semester) {
            $students = Student::select([
                'students.id',
                'students.user_id',
                'students.faculty_id',
                'students.department_id',
                'students.classroom_id',
                'students.fee_group_id',
                'students.student_number',
                'students.semester',
                'students.batch',
                'students.created_at'
            ])
                ->with(['user', 'faculty', 'department', 'classroom', 'feeGroup'])
                ->where('students.semester', $semester) // Match student's current semester with study plan semester
                ->whereHas('studyPlans', function ($query) use ($semester) {
                    $query->where('status', 'approved')
                        ->where('semester', $semester);
                })
                ->get();

            if ($students->count() > 0) {
                $studentsWithApprovedPlans[$semester] = $students;
            }
        }

        return inertia('Admin/SemesterManagement/Index', [
            'page_settings' => [
                'title' => 'Manajemen Semester',
                'subtitle' => 'Kelola kenaikan semester mahasiswa berdasarkan KRS yang telah disetujui.',
            ],
            'students_by_semester' => $studentsWithApprovedPlans,
            'semesters' => array_keys($studentsWithApprovedPlans),
            'current_semester' => request()->semester ?? null,
        ]);
    }

    /**
     * Show students from a specific semester
     */
    public function showSemester(Request $request, $semester): Response
    {
        $students = Student::query()
            ->select([
                'students.id',
                'students.user_id',
                'students.faculty_id',
                'students.department_id',
                'students.classroom_id',
                'students.fee_group_id',
                'students.student_number',
                'students.semester',
                'students.batch',
                'students.created_at'
            ])
            ->with(['user', 'faculty', 'department', 'classroom', 'feeGroup'])
            ->where('students.semester', $semester) // Only show students currently in this semester
            ->whereHas('studyPlans', function ($query) use ($semester) {
                $query->where('status', 'approved')
                    ->where('semester', $semester);
            })
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->paginate(request()->load ?? 10);

        return inertia('Admin/SemesterManagement/SemesterDetail', [
            'page_settings' => [
                'title' => 'Manajemen Semester ' . $semester,
                'subtitle' => 'Kelola mahasiswa semester ' . $semester . ' yang KRS-nya telah disetujui.',
            ],
            'students' => StudentResource::collection($students)->additional([
                'meta' => [
                    'has_pages' => $students->hasPages(),
                ],
            ]),
            'semester' => $semester,
            'state' => [
                'page' => request()->page ?? 1,
                'search' => request()->search ?? '',
                'load' => 10,
                'filter_by' => request()->filter_by ?? '',
            ],
        ]);
    }

    /**
     * Increase semester for selected students
     */
    public function increaseSemester(Request $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $studentIds = $request->student_ids;
            $currentSemester = $request->semester;
            $count = 0;

            foreach ($studentIds as $studentId) {
                $student = Student::find($studentId);

                if ($student) {
                    $student->update([
                        'semester' => $student->semester + 1,
                        'classroom_id' => null // Reset classroom_id
                    ]);
                    $count++;
                }
            }

            DB::commit();

            flashMessage(MessageType::UPDATED->message('Semester ' . $count . ' mahasiswa'));
            return back();
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return back();
        }
    }

    /**
     * Increase semester for all students in a specific semester
     */
    public function increaseSemesterAll(Request $request, $semester): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Get all students with approved study plans for this specific semester
            // and their current semester matches the requested semester
            $students = Student::where('semester', $semester)
                ->whereHas('studyPlans', function ($query) use ($semester) {
                    $query->where('status', 'approved')
                        ->where('semester', $semester);
                })->get();

            foreach ($students as $student) {
                $student->update([
                    'semester' => $student->semester + 1,
                    'classroom_id' => null // Reset classroom_id
                ]);
            }

            DB::commit();

            flashMessage(MessageType::UPDATED->message('Semester untuk ' . count($students) . ' mahasiswa'));
            return redirect()->route('admin.semester-management.index');
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return back();
        }
    }
}
