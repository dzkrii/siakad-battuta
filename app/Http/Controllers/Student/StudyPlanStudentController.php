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
use Illuminate\Support\Facades\Log;
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

    public function index(): Response
    {
        $studyPlans = StudyPlan::query()
            ->select(['id', 'student_id', 'academic_year_id', 'status', 'created_at'])
            ->where('student_id', auth()->user()->student->id)
            ->with(['academicYear'])
            ->latest('created_at')
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
                'load' => 10,
            ],
        ]);
    }

    public function selectClassroom(): Response
    {
        $student = auth()->user()->student;
        $availableClassrooms = Classroom::query()
            ->where('faculty_id', $student->faculty_id)
            ->where('department_id', $student->department_id)
            ->get();

        return inertia('Students/StudyPlans/SelectClassroom', [
            'page_settings' => [
                'title' => 'Pilih Kelas',
                'subtitle' => 'Anda belum memiliki kelas, silakan pilih kelas yang tersedia',
                'method' => 'POST',
                'action' => route('students.study-plans.store-classroom'),
            ],
            'classrooms' => $availableClassrooms,
        ]);
    }

    public function storeClassroom(Request $request): RedirectResponse
    {
        $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
        ]);

        $student = auth()->user()->student;
        $student->update([
            'classroom_id' => $request->classroom_id,
        ]);

        flashMessage('Kelas berhasil dipilih!', 'success');
        return to_route('students.study-plans.create');
    }

    public function create(): Response | RedirectResponse
    {
        if (!activeAcademicYear()) return back();

        // Periksa apakah mahasiswa sudah memiliki kelas
        $student = auth()->user()->student;
        if (!$student->classroom_id) {
            // Redirect ke halaman pemilihan kelas jika belum memiliki kelas
            return to_route('students.study-plans.select-classroom');
        }

        $schedules = Schedule::query()
            ->where('faculty_id', auth()->user()->student->faculty_id)
            ->where('department_id', auth()->user()->student->department_id)
            ->where('classroom_id', auth()->user()->student->classroom_id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->with(['course', 'classroom'])
            ->withCount(['studyPlans as taken_quota' => fn($query) => $query->where('academic_year_id', activeAcademicYear()->id)])
            ->orderByDesc('day_of_week')
            ->get();

        if ($schedules->isEmpty()) {
            flashMessage('Tidak ada jadwal yang tersedia....', 'warning');
            return to_route('students.study-plans.index');
        }

        $studyPlan = StudyPlan::query()
            ->where('student_id', auth()->user()->student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', auth()->user()->student->semester)
            ->whereIn('status', [StudyPlanStatus::PENDING, StudyPlanStatus::APPROVED])
            ->exists();

        if ($studyPlan) {
            flashMessage('Anda sudah mengajukan kartu rencana studi', 'warning');
            return to_route('students.study-plans.index');
        }

        return inertia('Students/StudyPlans/Create', [
            'page_settings' => [
                'title' => 'Tambah kartu rencana studi',
                'subtitle' => 'Harap pilih mata kuliah yang sesuai dengan kelas anda',
                'method' => 'POST',
                'action' => route('students.study-plans.store'),
            ],
            'schedules' => ScheduleResource::collection($schedules),
        ]);
    }

    public function store(StudyPlanStudentRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $studyPlan = StudyPlan::create([
                'student_id' => auth()->user()->student->id,
                'academic_year_id' => activeAcademicYear()->id,
                'semester' => auth()->user()->student->semester,
            ]);

            $studyPlan->schedules()->attach($request->schedule_id);

            DB::commit();

            flashMessage('Berhasil mengajukan kartu rencana studi');
            return to_route('students.study-plans.index');
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('students.study-plans.index');
        }
    }

    public function show(StudyPlan $studyPlan): Response
    {
        return inertia('Students/StudyPlans/Show', [
            'page_settings' => [
                'title' => 'Detail kartu rencana studi',
                'subtitle' => 'Anda dapat melihat kartu rencana studi yang sudah anda ajukan sebelumnya',
            ],
            'studyPlan' => new StudyPlanScheduleStudentResource($studyPlan->load('schedules')),
        ]);
    }

    public function downloadPdf(StudyPlan $studyPlan)
    {
        $studyPlan->load(['schedules.course', 'schedules.classroom', 'academicYear', 'student']);

        $pdf = Pdf::loadView('pdf.study-plan', compact('studyPlan'));

        $pdf->setPaper('A4', 'potrait');

        // Bersihkan nama tahun akademik dari karakter yang tidak diperbolehkan
        // $academicYear = str_replace(['/', '\\'], '-', $studyPlan->academicYear->name);

        // return response()->streamDownload(
        //     function () use ($pdf) {
        //         echo $pdf->output();
        //     },
        //     // 'KRS_' . auth()->user()->name . '_' . $academicYear . '.pdf'
        //     'KRS_' . auth()->user()->name . '.pdf'
        // );

        // Tampilkan PDF di browser (preview) dengan nama file spesifik
        return $pdf->stream('KRS_' . auth()->user()->name . '.pdf');
    }
}
