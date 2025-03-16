<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Http\Resources\Admin\StudentResource;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\FeeGroup;
use App\Models\Student;
use App\Models\User;
use App\Traits\HasFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Response;
use Throwable;

class StudentController extends Controller implements HasMiddleware
{
    use HasFile;

    public static function middleware(): array
    {
        return [
            new Middleware('validateDepartment', only: ['store', 'update']),
        ];
    }

    public function index(): Response
    {
        $students = Student::query()
            ->select(['students.id', 'students.user_id', 'students.faculty_id', 'students.department_id', 'students.classroom_id', 'students.fee_group_id', 'students.student_number', 'students.semester', 'students.batch', 'students.created_at'])
            ->filter(request()->only(['search']))
            ->sorting(request()->only(['field', 'direction']))
            ->with(['user', 'faculty', 'department', 'classroom', 'feeGroup'])
            ->whereHas('user', function ($query) {
                $query->whereHas('roles', fn($query) => $query->where('name', 'Student'));
            })
            ->paginate(request()->load ?? 10);

        return inertia('Admin/Students/Index', [
            'page_settings' => [
                'title' => 'Mahasiswa',
                'subtitle' => 'Menampilkan semua data mahasiswa yang tersedia pada Universitas Battuta.',
            ],
            'students' => StudentResource::collection($students)->additional([
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
        return inertia('Admin/Students/Create', [
            'page_settings' => [
                'title' => 'Tambah Mahasiswa',
                'subtitle' => 'Buat mahasiswa baru disini. Klik simpan setelah selesai.',
                'method' => 'POST',
                'action' => route('admin.students.store'),
            ],
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'departments' => Department::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'feeGroups' => FeeGroup::query()->select(['id', 'group', 'amount'])->orderBy('group')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => 'Golongan ' . $item->group . ' - ' . 'Rp ' . number_format($item->amount, 0, ',', '.'),
            ]),
            'classrooms' => Classroom::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $user = User::create([
                'name' => $request->name,
                'email' => $request->student_number . '@battuta.ac.id',
                'password' => Hash::make($request->password),
                'avatar' => $this->upload_file($request, 'avatar', 'users'),
            ]);

            $user->student()->create([
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'fee_group_id' => $request->fee_group_id,
                'classroom_id' => $request->classroom_id,
                'student_number' => $request->student_number,
                'semester' => $request->semester,
                'batch' => $request->batch,
            ]);

            $user->assignRole('Student');
            DB::commit();

            flashMessage(MessageType::CREATED->message('Mahasiswa'));
            return to_route('admin.students.index');
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.students.index');
        }
    }

    public function edit(Student $student): Response
    {
        return inertia('Admin/Students/Edit', [
            'page_settings' => [
                'title' => 'Edit Mahasiswa',
                'subtitle' => 'Edit mahasiswa disini. Klik simpan setelah selesai.',
                'method' => 'PUT',
                'action' => route('admin.students.update', $student),
            ],
            'student' => $student->load('user'),
            'faculties' => Faculty::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'departments' => Department::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
            'feeGroups' => FeeGroup::query()->select(['id', 'group', 'amount'])->orderBy('group')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => 'Golongan ' . $item->group . ' - ' . 'Rp ' . number_format($item->amount, 0, ',', '.'),
            ]),
            'classrooms' => Classroom::query()->select(['id', 'name'])->orderBy('name')->get()->map(fn($item) => [
                'value' => $item->id,
                'label' => $item->name,
            ]),
        ]);
    }

    public function update(Student $student, StudentRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $student->update([
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'fee_group_id' => $request->fee_group_id,
                'classroom_id' => $request->classroom_id,
                'student_number' => $request->student_number,
                'semester' => $request->semester,
                'batch' => $request->batch,
            ]);

            $student->user()->update([
                'name' => $request->name,
                'email' => $request->student_number . '@battuta.ac.id',
                'password' => $request->password ? Hash::make($request->password) : $student->user->password,
                'avatar' => $this->update_file($request, $student->user, 'avatar', 'users'),
            ]);

            DB::commit();

            flashMessage(MessageType::UPDATED->message('Mahasiswa'));
            return to_route('admin.students.index');
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.students.index');
        }
    }

    public function destroy(Student $student): RedirectResponse
    {
        try {
            $this->delete_file($student->user, 'avatar');
            $student->user()->delete();
            $student->delete();
            flashMessage(MessageType::DELETED->message('Mahasiswa'));
            return to_route('admin.students.index');
        } catch (Throwable $e) {
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('admin.students.index');
        }
    }

    public function removeClassroom($studentId)
    {
        $student = Student::findOrFail($studentId);
        $student->classroom_id = null;
        $student->save();

        return redirect()->back()->with('message', 'Kelas mahasiswa berhasil dihapus');
    }
}
