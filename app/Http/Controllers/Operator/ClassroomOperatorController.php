<?php

namespace App\Http\Controllers\Operator;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassroomRequest;
use App\Http\Requests\Operator\ClassroomOperatorRequest;
use App\Http\Resources\Operator\ClassroomOperatorResource;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class ClassroomOperatorController extends Controller
{
    public function index(): Response
    {
        $classrooms = Classroom::query()
            ->select(['id', 'faculty_id', 'department_id', 'academic_year_id', 'name', 'slug', 'created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->where('faculty_id', auth()->user()->operator->faculty_id)
            ->where('department_id', auth()->user()->operator->department_id)
            ->with(['academicYear'])
            ->paginate(request()->load ?? 10);

        $faculty_name = auth()->user()->operator->faculty?->name;
        $department_name = auth()->user()->operator->department?->name;

        return inertia('Operators/Classrooms/Index', [
            'page_settings' => [
                'title' => 'Kelas',
                'subtitle' => 'Menampilkan semua kelas yang ada di {$faculty_name}, Program Studi {$department_name}.',
            ],
            'classrooms' => ClassroomOperatorResource::collection($classrooms)->additional([
                'meta' => [
                    'has_pages' => $classrooms->hasPages(),
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
        return inertia('Operators/Classrooms/Create', [
            'page_settings' => [
                'title' => 'Tambah Kelas',
                'subtitle' => 'Buat kelas baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('operators.classrooms.store'),
            ],
        ]);
    }

    public function store(ClassroomOperatorRequest $request): RedirectResponse
    {
        try {
            Classroom::create([
                'faculty_id' => auth()->user()->operator->faculty_id,
                'department_id' => auth()->user()->operator->department_id,
                'academic_year_id' => activeAcademicYear()->id,
                'name' => $request->name,
            ]);

            flashMessage(MessageType::CREATED->message('Kelas'));
            return to_route('operators.classrooms.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.classrooms.index');
        }
    }

    public function edit(Classroom $classroom): Response
    {
        return inertia('Operators/Classrooms/Edit', [
            'page_settings' => [
                'title' => 'Edit Kelas',
                'subtitle' => 'Edit kelas disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('operators.classrooms.update', $classroom),
            ],
            'classroom' => $classroom->load(['academicYear']),
        ]);
    }

    public function update(Classroom $classroom, ClassroomOperatorRequest $request): RedirectResponse
    {
        try {
            $classroom->update([
                'name' => $request->name,
            ]);

            flashMessage(MessageType::UPDATED->message('Kelas'));
            return to_route('operators.classrooms.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.classrooms.index');
        }
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        try {
            $classroom->delete();

            flashMessage(MessageType::DELETED->message('Kelas'));
            return to_route('operators.classrooms.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.classrooms.index');
        }
    }
}
