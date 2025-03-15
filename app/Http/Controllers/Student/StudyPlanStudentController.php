<?php

namespace App\Http\Controllers\Student;

use App\Enums\MessageType;
use App\Enums\StudyPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudyPlanStudentRequest;
use App\Http\Resources\Admin\ScheduleResource;
use App\Http\Resources\Student\StudyPlanScheduleStudentResource;
use App\Http\Resources\Student\StudyPlanStudentResource;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\StudyPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Inertia\Response;
use Throwable;

class StudyPlanStudentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('checkActiveAcademicYear', except: ['index']),
            // new Middleware('checkFeeStudent', except: ['index']),
        ];
    }

    /**
     * Display a listing of the study plans.
     */
    public function index(): Response
    {
        $student = auth()->user()->student;

        // Get student information with relationships
        $studentInfo = $student->load(['faculty', 'department', 'classroom']);

        // Check if student can create a new study plan
        $canCreateStudyPlan = !StudyPlan::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear() ? activeAcademicYear()->id : null)
            ->where('semester', $student->semester)
            ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
            ->exists();

        // Fetch study plans
        $studyPlans = StudyPlan::query()
            ->select(['id', 'student_id', 'academic_year_id', 'status', 'created_at', 'semester'])
            ->where('student_id', $student->id)
            ->with(['academicYear'])
            ->when(request()->search, function ($query, $search) {
                return $query->whereHas('academicYear', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when(
                request()->field && request()->direction,
                function ($query) {
                    if (request()->field === 'academic_year_id') {
                        return $query->join('academic_years', 'study_plans.academic_year_id', '=', 'academic_years.id')
                            ->orderBy('academic_years.name', request()->direction)
                            ->select('study_plans.*');
                    }
                    return $query->orderBy(request()->field, request()->direction);
                },
                function ($query) {
                    return $query->latest('created_at');
                }
            )
            ->paginate(request()->load ?? 10);

        return inertia('Students/StudyPlans/Index', [
            'page_settings' => [
                'title' => 'Kartu Rencana Studi',
                'subtitle' => 'Menampilkan semua kartu rencana studi anda',
            ],
            'studyPlans' => StudyPlanStudentResource::collection($studyPlans)->additional([
                'meta' => [
                    'has_pages' => $studyPlans->hasPages(),
                ],
            ]),
            'state' => [
                'page' => request()->page ?? 1,
                'search' => request()->search ?? '',
                'load' => request()->load ?? 10,
                'field' => request()->field ?? 'created_at',
                'direction' => request()->direction ?? 'desc',
            ],
            'can_create_study_plan' => $canCreateStudyPlan && activeAcademicYear() !== null,
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->name,
                'nim' => $student->student_number,
                'semester' => $student->semester,
                'faculty_id' => $student->faculty_id,
                'department_id' => $student->department_id,
                'classroom_id' => $student->classroom_id,
                'faculty_name' => $studentInfo->faculty ? $studentInfo->faculty->name : null,
                'department_name' => $studentInfo->department ? $studentInfo->department->name : null,
                'classroom_name' => $studentInfo->classroom ? $studentInfo->classroom->name : null,
            ],
        ]);
    }

    /**
     * Show the classroom selection form.
     */
    public function selectClassroom(): Response
    {
        $student = auth()->user()->student;
        $currentSemester = $student->semester;

        // Get the current classroom if exists
        $currentClassroom = $student->classroom;

        // Check if student can change classroom
        $canChangeClassroom = !StudyPlan::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
            ->exists();

        // Get available classrooms filtered by department, faculty, and semester
        $availableClassrooms = Classroom::query()
            ->select([
                'classrooms.id',
                'classrooms.name',
                'faculties.name as faculty_name',
                'departments.name as department_name',
            ])
            ->join('faculties', 'faculties.id', '=', 'classrooms.faculty_id')
            ->join('departments', 'departments.id', '=', 'classrooms.department_id')
            ->where('classrooms.faculty_id', $student->faculty_id)
            ->where('classrooms.department_id', $student->department_id)
            ->where('classrooms.semester', $currentSemester)
            ->get();

        return inertia('Students/StudyPlans/SelectClassroom', [
            'page_settings' => [
                'title' => $currentClassroom ? 'Kelas Anda Saat Ini' : 'Pilih Kelas',
                'subtitle' => $currentClassroom
                    ? 'Anda sudah memiliki kelas, Anda dapat mengubahnya jika belum mengajukan KRS'
                    : 'Silakan pilih kelas yang sesuai dengan semester Anda',
                'method' => 'POST',
                'action' => route('students.study-plans.store-classroom'),
            ],
            'classrooms' => $availableClassrooms,
            'current_classroom' => $currentClassroom,
            'can_change_classroom' => $canChangeClassroom,
        ]);
    }

    /**
     * Store the selected classroom.
     */
    public function storeClassroom(Request $request): RedirectResponse
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $student = auth()->user()->student;

        // Check if student can change classroom
        $canChangeClassroom = !StudyPlan::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
            ->exists();

        if (!$canChangeClassroom) {
            flashMessage('Anda tidak dapat mengubah kelas karena sudah memiliki KRS yang diajukan!', 'error');
            return to_route('students.study-plans.index');
        }

        // Verify that the selected classroom matches the student's semester
        $classroom = Classroom::findOrFail($request->classroom_id);
        if ($classroom->semester != $student->semester) {
            flashMessage('Kelas yang dipilih tidak sesuai dengan semester Anda!', 'error');
            return back();
        }

        $student->update([
            'classroom_id' => $request->classroom_id,
        ]);

        flashMessage('Kelas berhasil ' . ($student->wasChanged('classroom_id') ? 'diubah' : 'dipilih') . '!', 'success');
        return to_route('students.study-plans.create');
    }

    /**
     * Show the form for creating a new study plan.
     */
    public function create(): Response | RedirectResponse
    {
        if (!activeAcademicYear()) {
            flashMessage('Tidak ada tahun akademik yang aktif!', 'error');
            return back();
        }

        // Check if student has a classroom
        $student = auth()->user()->student;
        if (!$student->classroom_id) {
            // Redirect to classroom selection page
            return to_route('students.study-plans.select-classroom');
        }

        // Check for existing study plan in current semester
        $existingStudyPlan = StudyPlan::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', $student->semester)
            ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
            ->exists();

        if ($existingStudyPlan) {
            flashMessage('Anda sudah mengajukan kartu rencana studi untuk semester ini', 'warning');
            return to_route('students.study-plans.index');
        }

        // Get available schedules for student's classroom
        $schedules = Schedule::query()
            ->where('faculty_id', $student->faculty_id)
            ->where('department_id', $student->department_id)
            ->where('classroom_id', $student->classroom_id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->with(['course', 'classroom'])
            ->withCount([
                'studyPlans as taken_quota' => function ($query) {
                    $query->where('academic_year_id', activeAcademicYear()->id);
                }
            ])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            flashMessage('Tidak ada jadwal yang tersedia untuk kelas Anda', 'warning');
            return to_route('students.study-plans.index');
        }

        return inertia('Students/StudyPlans/Create', [
            'page_settings' => [
                'title' => 'Tambah Kartu Rencana Studi',
                'subtitle' => 'Pilih mata kuliah yang akan diajukan sebagai KRS pada semester ini',
                'method' => 'POST',
                'action' => route('students.study-plans.store'),
            ],
            'schedules' => ScheduleResource::collection($schedules),
            'current_classroom' => $student->classroom,
        ]);
    }

    /**
     * Store a newly created study plan.
     */
    public function store(StudyPlanStudentRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $student = auth()->user()->student;

            // Check for existing study plans
            $existingStudyPlan = StudyPlan::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', activeAcademicYear()->id)
                ->where('semester', $student->semester)
                ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
                ->exists();

            if ($existingStudyPlan) {
                DB::rollBack();
                flashMessage('Anda sudah mengajukan kartu rencana studi untuk semester ini', 'warning');
                return to_route('students.study-plans.index');
            }

            // Validate schedule availability
            if (empty($request->schedule_id)) {
                DB::rollBack();
                flashMessage('Silakan pilih minimal satu mata kuliah', 'error');
                return back();
            }

            // Create study plan
            $studyPlan = StudyPlan::create([
                'student_id' => $student->id,
                'academic_year_id' => activeAcademicYear()->id,
                'semester' => $student->semester,
                'status' => StudyPlanStatus::PENDING,
            ]);

            // Attach schedules
            $studyPlan->schedules()->attach($request->schedule_id);

            DB::commit();

            flashMessage('Berhasil mengajukan kartu rencana studi', 'success');
            return to_route('students.study-plans.index');
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('students.study-plans.index');
        }
    }

    /**
     * Display the specified study plan.
     */
    public function show(StudyPlan $studyPlan): Response
    {
        // Authorization check - only show the study plan if it belongs to the logged-in student
        if ($studyPlan->student_id !== auth()->user()->student->id) {
            abort(403, 'Unauthorized action.');
        }

        return inertia('Students/StudyPlans/Show', [
            'page_settings' => [
                'title' => 'Detail Kartu Rencana Studi',
                'subtitle' => 'Informasi detail mengenai kartu rencana studi yang sudah Anda ajukan',
            ],
            'studyPlan' => new StudyPlanScheduleStudentResource($studyPlan->load([
                'schedules.course',
                'schedules.classroom',
                'academicYear',
                'student'
            ])),
        ]);
    }

    /**
     * Download the study plan as PDF.
     */
    public function downloadPdf(StudyPlan $studyPlan)
    {
        // Authorization check - only download the study plan if it belongs to the logged-in student
        if ($studyPlan->student_id !== auth()->user()->student->id) {
            abort(403, 'Unauthorized action.');
        }

        $studyPlan->load(['schedules.course', 'schedules.classroom', 'academicYear', 'student']);

        $pdf = Pdf::loadView('pdf.study-plan', compact('studyPlan'));
        $pdf->setPaper('A4', 'potrait');

        // Return PDF as a download
        return $pdf->stream('KRS_' . auth()->user()->name . '_' . $studyPlan->academicYear->semester . '.pdf');
    }
}
