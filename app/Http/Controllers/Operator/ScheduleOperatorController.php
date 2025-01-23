<?php

namespace App\Http\Controllers\Operator;

use App\Enums\MessageType;
use App\Enums\ScheduleDay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleRequest;
use App\Http\Requests\Operator\ScheduleOperatorRequest;
use App\Http\Resources\Operator\ScheduleOperatorResource;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

class ScheduleOperatorController extends Controller
{
    public function index(): Response
    {
        $schedules = Schedule::query()
            ->select(['schedules.id', 'schedules.faculty_id', 'schedules.department_id', 'schedules.course_id', 'schedules.classroom_id', 'schedules.academic_year_id', 'schedules.start_time', 'schedules.end_time', 'schedules.day_of_week', 'schedules.quota', 'schedules.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->where('schedules.faculty_id', auth()->user()->operator->faculty_id)
            ->where('schedules.department_id', auth()->user()->operator->department_id)
            ->with(['course', 'classroom', 'academicYear'])
            ->paginate(request()->load ?? 10);

        $faculty_name = auth()->user()->operator->faculty?->name;
        $department_name = auth()->user()->operator->department?->name;

        return inertia('Operators/Schedules/Index', [
            'page_settings' => [
                'title' => 'Jadwal',
                'subtitle' => "Menampilkan semua data jadwal yang ada di {$faculty_name} - Program Studi {$department_name} Universitas Battuta.",
            ],
            'schedules' => ScheduleOperatorResource::collection($schedules)->additional([
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
        return inertia('Operators/Schedules/Create', [
            'page_settings' => [
                'title' => 'Tambah Jadwal',
                'subtitle' => 'Buat jadwal baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('operators.schedules.store'),
            ],
            'courses' => Course::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'classrooms' => Classroom::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'days' => ScheduleDay::options(),
        ]);
    }

    public function store(ScheduleOperatorRequest $request): RedirectResponse
    {
        try {
            Schedule::create([
                'faculty_id' => auth()->user()->operator->faculty_id,
                'department_id' => auth()->user()->operator->faculty_id,
                'course_id' => $request->course_id,
                'classroom_id' => $request->classroom_id,
                'academic_year_id' => activeAcademicYear()->id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'day_of_week' => $request->day_of_week,
                'quota' => $request->quota,
            ]);

            flashMessage(MessageType::CREATED->message('Jadwal'));
            return to_route('operators.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.schedules.index');
        }
    }

    public function edit(Schedule $schedule): Response
    {
        return inertia('Operators/Schedules/Edit', [
            'page_settings' => [
                'title' => 'Edit Jadwal',
                'subtitle' => 'Edit jadwal disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('operators.schedules.update', $schedule),
            ],
            'schedule' => $schedule,
            'courses' => Course::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'classrooms' => Classroom::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'days' => ScheduleDay::options(),
        ]);
    }

    public function update(Schedule $schedule, ScheduleOperatorRequest $request): RedirectResponse
    {
        try {
            $schedule->update([
                'course_id' => $request->course_id,
                'classroom_id' => $request->classroom_id,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'day_of_week' => $request->day_of_week,
                'quota' => $request->quota,
            ]);

            flashMessage(MessageType::UPDATED->message('Jadwal'));
            return to_route('operators.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.schedules.index');
        }
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        try {
            $schedule->delete();

            flashMessage(MessageType::DELETED->message('Jadwal'));
            return to_route('operators.schedules.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.schedules.index');
        }
    }
}
