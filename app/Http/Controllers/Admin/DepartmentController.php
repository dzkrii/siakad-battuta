<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Http\Resources\Admin\DepartmentResource;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::query()
            ->select(['id', 'faculty_id', 'name', 'code', 'slug', 'created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->with('faculty')
            ->paginate(request()->load ?? 10);

        return inertia('Admin/Departments/Index', [
            'page_settings' => [
                'title' => 'Program Studi',
                'subtitle' => 'Menampilkan semua data program studi yang tersedia pada Universitas Battuta.',
            ],
            'departments' => DepartmentResource::collection($departments)->additional([
                'meta' => [
                    'has_pages' => $departments->hasPages(),
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
        return inertia('Admin/Departments/Create', [
            'page_settings' => [
                'title' => 'Tambah Program Studi',
                'subtitle' => 'Buat program studi baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('admin.departments.store'),
            ],
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
        ]);
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        try {
            Department::create([
                'faculty_id' => $request->faculty_id,
                'name' => $request->name,
                'code' => str()->random(6),
            ]);

            flashMessage(MessageType::CREATED->message('Program Studi'));
            return to_route('admin.departments.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.departments.index');
        }
    }

    public function edit(Department $department): Response
    {
        return inertia('Admin/Departments/Edit', [
            'page_settings' => [
                'title' => 'Edit Program Studi',
                'subtitle' => 'Edit program studi disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('admin.departments.update', $department),
            ],
            'department' => $department,
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
        ]);
    }

    public function update(Department $department, DepartmentRequest $request): RedirectResponse
    {
        try {
            $department->update([
                'faculty_id' => $request->faculty_id,
                'name' => $request->name,
            ]);

            flashMessage(MessageType::UPDATED->message('Program Studi'));
            return to_route('admin.departments.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.departments.index');
        }
    }

    public function destroy(Department $department): RedirectResponse
    {
        try {
            $department->delete();

            flashMessage(MessageType::DELETED->message('Program Studi'));
            return to_route('admin.departments.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.departments.index');
        }
    }
}
