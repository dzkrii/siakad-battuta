<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacultyRequest;
use App\Http\Resources\Admin\FacultyResource;
use App\Models\Faculty;
use App\Traits\HasFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class FacultyController extends Controller
{
    use HasFile;
    public function index(): Response
    {
        $faculties = Faculty::query()
            ->select(['id', 'name', 'code', 'logo', 'slug', 'created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->paginate(request()->load ?? 10);

        return inertia('Admin/Faculties/Index', [
            'page_settings' => [
                'title' => 'Fakultas',
                'subtitle' => 'Menampilkan semua data fakultas yang tersedia pada Universitas Battuta.',
            ],
            'faculties' => FacultyResource::collection($faculties)->additional([
                'meta' => [
                    'has_pages' => $faculties->hasPages(),
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
        return inertia('Admin/Faculties/Create', [
            'page_settings' => [
                'title' => 'Tambah Fakultas',
                'subtitle' => 'Buat fakultas baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('admin.faculties.store'),
            ],
        ]);
    }

    public function store(FacultyRequest $request): RedirectResponse
    {
        try {
            Faculty::create([
                'name' => $request->name,
                'code' => str()->random(6),
                'logo' => $this->upload_file($request, 'logo', 'faculties'),
            ]);

            flashMessage(MessageType::CREATED->message('Fakultas'));
            return to_route('admin.faculties.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.faculties.index');
        }
    }

    public function edit(Faculty $faculty): Response
    {
        return inertia('Admin/Faculties/Edit', [
            'page_settings' => [
                'title' => 'Edit Fakultas',
                'subtitle' => 'Edit fakultas disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('admin.faculties.update', $faculty),
            ],
            'faculty' => $faculty,
        ]);
    }

    public function update(Faculty $faculty, FacultyRequest $request): RedirectResponse
    {
        try {
            $faculty->update([
                'name' => $request->name,
                'logo' => $this->update_file($request, $faculty, 'logo', 'faculties'),
            ]);

            flashMessage(MessageType::UPDATED->message('Fakultas'));
            return to_route('admin.faculties.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.faculties.index');
        }
    }

    public function destroy(Faculty $faculty): RedirectResponse
    {
        try {
            $this->delete_file($faculty, 'logo');
            $faculty->delete();

            flashMessage(MessageType::DELETED->message('Fakultas'));
            return to_route('admin.faculties.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.faculties.index');
        }
    }
}
