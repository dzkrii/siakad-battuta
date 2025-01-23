<?php

namespace App\Http\Controllers\Operator;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Operator\StudentOperatorRequest;
use App\Http\Resources\Operator\StudentOperatorResource;
use App\Models\Classroom;
use App\Models\FeeGroup;
use App\Models\Student;
use App\Models\User;
use App\Traits\HasFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;
use Throwable;

class StudentOperatorController extends Controller
{
    use HasFile;
    public function index(): Response
    {
        $students = Student::query()
            ->select(['students.id', 'students.user_id', 'students.faculty_id', 'students.department_id', 'students.fee_group_id', 'students.classroom_id', 'students.student_number', 'students.semester', 'students.batch', 'students.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->whereHas('user', function ($query) {
                $query->whereHas('roles', fn($query) => $query->where('name', 'Student'));
            })
            ->where('students.faculty_id', auth()->user()->operator->faculty_id)
            ->where('students.department_id', auth()->user()->operator->department_id)
            ->with(['user', 'classroom', 'feeGroup'])
            ->paginate(request()->load ?? 10);

        $faculty_name = auth()->user()->operator->faculty?->name;
        $department_name = auth()->user()->operator->department?->name;

        return inertia('Operators/Students/Index', [
            'page_settings' => [
                'title' => 'Mahasiswa',
                'subtitle' => "Menampilkan semua data mahasiswa yang ada di {$faculty_name}, Program Studi {$department_name}.",
            ],
            'students' => StudentOperatorResource::collection($students)->additional([
                'meta' => [
                    'has_pages' => $students->hasPages(),
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
        return inertia('Operators/Students/Create', [
            'page_settings' => [
                'title' => 'Tambah Mahasiswa',
                'subtitle' => 'Buat mahasiswa baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('operators.students.store'),
            ],
            'classrooms' => Classroom::query()
                ->select(['id', 'name'])
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->orderBy('name')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'feeGroups' => FeeGroup::query()
                ->select(['id', 'group', 'amount'])
                ->orderBy('group')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => "Golongan " . $item->group . " - " . "Rp " . number_format($item->amount, 0, ',', '.'),
                ]),
        ]);
    }

    public function store(StudentOperatorRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'avatar' => $this->upload_file($request, 'avatar', 'users'),
            ]);

            $user->student()->create([
                'faculty_id' => auth()->user()->operator->faculty_id,
                'department_id' => auth()->user()->operator->department_id,
                'student_number' => $request->student_number,
                'semester' => $request->semester,
                'batch' => $request->batch,
                'fee_group_id' => $request->fee_group_id,
                'classroom_id' => $request->classroom_id,
            ]);

            $user->assignRole('Student');

            DB::commit();

            flashMessage(MessageType::CREATED->message('Mahasiswa'));
            return to_route('operators.students.index');
        } catch (Throwable $e) {
            DB::rollBack();

            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.students.index');
        }
    }

    public function edit(Student $student): Response
    {
        return inertia('Operators/Students/Edit', [
            'page_settings' => [
                'title' => 'Edit Mahasiswa',
                'subtitle' => 'Edit mahasiswa disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('operators.students.update', $student),
            ],
            'student' => $student->load('user'),
            'classrooms' => Classroom::query()
                ->select(['id', 'name'])
                ->where('faculty_id', auth()->user()->operator->faculty_id)
                ->where('department_id', auth()->user()->operator->department_id)
                ->orderBy('name')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => $item->name,
                ]),
            'feeGroups' => FeeGroup::query()
                ->select(['id', 'group', 'amount'])
                ->orderBy('group')
                ->get()
                ->map(fn($item) => [
                    'value' => $item->id,
                    'label' => "Golongan " . $item->group . " - " . "Rp " . number_format($item->amount, 0, ',', '.'),
                ]),
        ]);
    }

    public function update(Student $student, StudentOperatorRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $student->update([
                'student_number' => $request->student_number,
                'semester' => $request->semester,
                'batch' => $request->batch,
                'fee_group_id' => $request->fee_group_id,
                'classroom_id' => $request->classroom_id,
            ]);

            $student->user()->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $student->user->password,
                'avatar' => $this->update_file($request, $student->user, 'avatar', 'users'),
            ]);


            DB::commit();

            flashMessage(MessageType::UPDATED->message('Mahasiswa'));
            return to_route('operators.students.index');
        } catch (Throwable $e) {
            DB::rollBack();

            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.students.index');
        }
    }

    public function destroy(Student $student): RedirectResponse
    {
        try {
            $this->delete_file($student->user, 'avatar');
            $student->user()->delete();
            $student->delete();

            flashMessage(MessageType::DELETED->message('Mahasiswa'));
            return to_route('operators.students.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('operators.students.index');
        }
    }
}
