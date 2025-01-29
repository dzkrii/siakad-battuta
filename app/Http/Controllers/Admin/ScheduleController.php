<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Enums\ScheduleDay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Http\Resources\Admin\ScheduleResource;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Response;
use Throwable;

class ScheduleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('validateClassroom', only: ['store', 'update']),
            new Middleware('validateCourse', only: ['store', 'update']),
            new Middleware('validateDepartment', only: ['store', 'update']),
        ];
    }

    public function index(): Response
    {
        $schedules = Schedule::query()
            ->select(['schedules.id', 'schedules.faculty_id', 'schedules.department_id', 'schedules.course_id', 'schedules.classroom_id', 'schedules.academic_year_id', 'schedules.start_time', 'schedules.end_time', 'schedules.day_of_week', 'schedules.quota', 'schedules.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->with(['faculty', 'department', 'course', 'classroom', 'academicYear'])
            ->paginate(request()->load ?? 10);

        return inertia('Admin/Schedules/Index', [
            'page_settings' => [
                'title' => 'Jadwal',
                'subtitle' => 'Menampilkan semua data jadwal yang tersedia pada Universitas Battuta.',
            ],
            'schedules' => ScheduleResource::collection($schedules)->additional([
                'meta' => [
                    'has_pages' => $schedules->hasPages(),
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
        return inertia('Admin/Schedules/Create', [
            'page_settings' => [
                'title' => 'Tambah Jadwal',
                'subtitle' => 'Buat jadwal baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('admin.schedules.store'),
            ],
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'departments' => Department::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'courses' => Course::query()
                ->with(['teacher']) // Memuat relasi teacher
                ->select(['id', 'name', 'teacher_id'])
                ->orderBy('name')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                    'teacher' => $item->teacher?->user?->name, // Menambahkan nama dosen
                ]),
            'classrooms' => Classroom::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'days' => ScheduleDay::options(),
        ]);
    }

    public function store(ScheduleRequest $request): RedirectResponse
    {
        try {
            Schedule::create([
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'course_id' => $request->course_id,
                'classroom_id' => $request->classroom_id,
                'academic_year_id' => activeAcademicYear()->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'day_of_week' => $request->day_of_week,
                'quota' => $request->quota,
            ]);

            flashMessage(MessageType::CREATED->message('Jadwal'));
            return to_route('admin.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.schedules.index');
        }
    }

    public function edit(Schedule $schedule): Response
    {
        return inertia('Admin/Schedules/Edit', [
            'page_settings' => [
                'title' => 'Edit Jadwal',
                'subtitle' => 'Edit jadwal disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('admin.schedules.update', $schedule),
            ],
            'schedule' => $schedule,
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'departments' => Department::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'courses' => Course::query()
                ->with(['teacher']) // Memuat relasi teacher
                ->select(['id', 'name', 'teacher_id'])
                ->orderBy('name')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                    'teacher' => $item->teacher?->user?->name, // Menambahkan nama dosen
                ]),
            'classrooms' => Classroom::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'days' => ScheduleDay::options(),
        ]);
    }

    public function update(Schedule $schedule, ScheduleRequest $request): RedirectResponse
    {
        try {
            $schedule->update([
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'course_id' => $request->course_id,
                'classroom_id' => $request->classroom_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'day_of_week' => $request->day_of_week,
                'quota' => $request->quota,
            ]);

            flashMessage(MessageType::UPDATED->message('Jadwal'));
            return to_route('admin.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.schedules.index');
        }
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        try {
            $schedule->delete();

            flashMessage(MessageType::DELETED->message('Jadwal'));
            return to_route('admin.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.schedules.index');
        }
    }
}
