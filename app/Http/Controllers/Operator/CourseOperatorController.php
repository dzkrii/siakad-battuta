<?php

namespace App\Http\Controllers\Operator;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\CourseOperatorRequest;
use App\Http\Resources\Operator\CourseOperatorResource;
use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class CourseOperatorController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->select(['courses.id', 'courses.faculty_id', 'courses.department_id', 'courses.teacher_id', 'courses.academic_year_id', 'courses.kode_matkul', 'courses.name', 'courses.credit', 'courses.semester', 'courses.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->where('courses.faculty_id', auth()->user()->operator->faculty_id)
            ->where('courses.department_id', auth()->user()->operator->department_id)
            ->with(['teacher', 'academicYear'])
            ->paginate(request()->load ?? 10);

        $faculty_name = auth()->user()->operator->faculty?->name;
        $department_name = auth()->user()->operator->department?->name;

        return inertia('Operators/Courses/Index', [
            'page_settings' => [
                'title' => 'Mata Kuliah',
                'subtitle' => "Menampilkan semua data mata kuliah yang ada di {$faculty_name}, Program Studi {$department_name}.",
            ],
            'courses' => CourseOperatorResource::collection($courses)->additional([
                'meta' => [
                    'has_pages' => $courses->hasPages(),
                ],
            ]),
            'state' => [
                'page' => request()->page ?? 1,
                'search' => request()->search ?? '',
                'load' => 10,
            ],
        ]);
    }

    public function create(): Response
    {
        return inertia('Operators/Courses/Create', [
            'page_settings' => [
                'title' => 'Tambah Mata Kuliah',
                'subtitle' => 'Tambahkan mata kuliah baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('operators.courses.store'),
            ],
            'teachers' => Teacher::query()
                ->select(['id', 'user_id'])
                ->whereHas('user', function ($query) {
                    $query->whereHas('roles', function ($query) {
                        $query->where('name', 'Teacher');
                    })->orderBy('name');
                })
                // ->where('faculty_id', auth()->user()->operator->faculty_id)
                // ->where('department_id', auth()->user()->operator->department_id)
                ->with(['user'])
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->user?->name,
                    ];
                })
        ]);
    }

    public function store(CourseOperatorRequest $request): RedirectResponse
    {
        try {
            Course::create([
                'faculty_id' => auth()->user()->operator->faculty_id,
                'department_id' => auth()->user()->operator->department_id,
                'teacher_id' => $request->teacher_id,
                'academic_year_id' => activeAcademicYear()->id,
                'code' => $request->code,
                'name' => $request->name,
                'credit' => $request->credit,
                'semester' => $request->semester,
            ]);

            flashMessage(MessageType::CREATED->message('Mata kuliah'));
            return to_route('operators.courses.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.courses.index');
        }
    }

    public function edit(Course $course): Response
    {
        return inertia('Operators/Courses/Edit', [
            'page_settings' => [
                'title' => 'Edit Mata Kuliah',
                'subtitle' => 'Edit mata kuliah disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('operators.courses.update', $course),
            ],
            'course' => $course,
            'teachers' => Teacher::query()
                ->select(['id', 'user_id'])
                ->whereHas('user', function ($query) {
                    $query->whereHas('roles', function ($query) {
                        $query->where('name', 'Teacher');
                    })->orderBy('name');
                })
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->with(['user'])
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->id,
                        'label' => $item->user?->name,
                    ];
                })
        ]);
    }

    public function update(Course $course, CourseOperatorRequest $request): RedirectResponse
    {
        try {
            $course->update([
                'teacher_id' => $request->teacher_id,
                'code' => $request->code,
                'name' => $request->name,
                'credit' => $request->credit,
                'semester' => $request->semester,
            ]);

            flashMessage(MessageType::UPDATED->message('Mata kuliah'));
            return to_route('operators.courses.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.courses.index');
        }
    }

    public function destroy(Course $course): RedirectResponse
    {
        try {
            $course->delete();

            flashMessage(MessageType::DELETED->message('Mata kuliah'));
            return to_route('operators.courses.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.courses.index');
        }
    }
}
