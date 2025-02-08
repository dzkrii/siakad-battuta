<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\MessageType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CourseClassroomRequest;
use App\Http\Resources\Teacher\CourseStudentClassroomResource;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\StudyResult;
use App\Models\StudyResultGrade;
use App\Traits\CalculatesFinalScore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Response;
use Throwable;

class CourseClassroomController extends Controller
{
    use CalculatesFinalScore;
    public function index(Course $course, Classroom $classroom): Response
    {
        $schedule = Schedule::query()
            ->where('course_id', $course->id)
            ->where('classroom_id', $classroom->id)
            ->first();

        $students = Student::query()
            ->where('faculty_id', $classroom->faculty_id)
            ->where('department_id', $classroom->department_id)
            ->where('classroom_id', $classroom->id)
            ->filter(request()->only(['search']))
            ->wherehas('user', function ($query) {
                $query->whereHas('roles', fn($query) => $query->where('name', 'Student'));
            })
            ->whereHas('studyPlans', function ($query) use ($schedule) {
                $query->where('academic_year_id', activeAcademicYear()->id)
                    ->approved()
                    ->whereHas('schedules', fn($query) => $query->where('schedule_id', $schedule->id));
            })
            ->with([
                'user',
                'attendances' => fn($query) => $query->where('course_id', $course->id)->where('classroom_id', $classroom->id),
                'grades' => fn($query) => $query->where('course_id', $course->id)->where('classroom_id', $classroom->id),
            ])
            ->withCount([
                'attendances' => fn($query) => $query->where('course_id', $course->id)->where('classroom_id', $classroom->id),
            ])
            ->withSum(
                [
                    'grades as tasks_count' => fn($query) => $query
                        ->where('course_id', $course->id)
                        ->where('classroom_id', $classroom->id)
                        ->where('category', 'tugas')
                        ->whereNull('section')
                ],
                'grade',
            )
            ->withSum(
                [
                    'grades as uts_count' => fn($query) => $query
                        ->where('course_id', $course->id)
                        ->where('classroom_id', $classroom->id)
                        ->where('category', 'uts')
                        ->whereNull('section')
                ],
                'grade',
            )
            ->withSum(
                [
                    'grades as uas_count' => fn($query) => $query
                        ->where('course_id', $course->id)
                        ->where('classroom_id', $classroom->id)
                        ->where('category', 'uas')
                        ->whereNull('section')
                ],
                'grade',
            )
            ->get();

        return inertia('Teachers/Classrooms/Index', [
            'page_settings' => [
                'title' => "Kelas {$classroom->name} - Mata kuliah {$course->name}",
                'subtitle' => 'Menampilkan data mahasiswa',
                'method' => 'PUT',
                'action' => route('teachers.classrooms.sync', [$course, $classroom]),
            ],
            'course' => $course,
            'classroom' => $classroom,
            'students' => CourseStudentClassroomResource::collection($students),
            'state' => [
                'search' => request()->search ?? '',
                // 'search' => request()->search ?? '',
                // 'load' => 10,
            ],
        ]);
    }

    public function calculateGPA(int $studentId)
    {
        $student = Student::query()
            ->where('id', $studentId)
            ->first();

        $studyResult = StudyResult::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', $student->semester)
            ->first();

        if (!$studyResult) {
            Log::error("❌ Study result not found for student ID: $studentId");
            return 0;
        }

        $studyResultGrades = StudyResultGrade::query()
            ->where('study_result_id', $studyResult->id)
            ->get();

        Log::info("📊 Calculating GPA for student ID: $studentId");
        Log::info("Total study results: " . $studyResultGrades->count());

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($studyResultGrades as $grade) {
            $finalScore = min($grade->grade, 100);
            $gpaScore = ($finalScore / 100) * 4;
            $weight = $grade->weight_of_value;

            Log::info("📌 Grade ID: {$grade->id}, Final Score: $finalScore, GPA Score: $gpaScore, Weight: $weight");

            $totalScore += $gpaScore * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            $gpa = min(round($totalScore / $totalWeight, 2), 4);
            Log::info("✅ Final GPA for Student ID $studentId: $gpa");
            return min(round($totalScore / $totalWeight, 2), 4);
        }

        Log::warning("⚠️ Total weight is 0, returning GPA = 0 for Student ID $studentId");

        return 0;
    }

    public function updateGPA(int $studentId)
    {
        $student = Student::query()
            ->where('id', $studentId)
            ->first();

        $gpa = $this->calculateGPA($student->id);

        $studyResult = StudyResult::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', $student->semester)
            ->first();

        if ($studyResult) {
            $studyResult->update([
                'gpa' => $gpa,
            ]);
        }
    }

    public function sync(Course $course, Classroom $classroom, CourseClassroomRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $attendances = array_map(function ($attendance) {
                $attendance['created_at'] = Carbon::now();
                $attendance['updated_at'] = Carbon::now();
                return $attendance;
            }, $request->attendances);

            $grades = array_map(function ($grade) {
                $grade['created_at'] = Carbon::now();
                $grade['updated_at'] = Carbon::now();
                return $grade;
            }, $request->grades);

            $studentIds = collect($attendances)
                ->pluck('student_id')
                ->merge(collect($grades)->pluck('student_id'))
                ->unique()
                ->values();

            $studyResult = StudyResult::query()
                ->whereIn('student_id', $studentIds)
                ->get();

            Attendance::insert($attendances);
            Grade::insert($grades);

            $studyResult->each(function ($result) use ($course, $classroom) {
                $final_score = $this->calculateFinalScore(
                    attendancePercentage: (
                        $this->calculateAttendancePercentage(
                            $this->getAttendanceCount($result->student_id, $course->id, $classroom->id)
                        )
                    ),
                    taskPercentage: (
                        $this->calculateTaskPercentage(
                            $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'tugas')
                        )
                    ),
                    utsPercentage: (
                        $this->calculateUTSPercentage(
                            $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'uts')
                        )
                    ),
                    uasPercentage: (
                        $this->calculateUASPercentage(
                            $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'uas')
                        )
                    )
                );

                $grades = StudyResultGrade::updateOrCreate([
                    'study_result_id' => $result->id,
                    'course_id' => $course->id,
                ], [
                    'grade' => $final_score,
                    'letter' => getLetterGrade($final_score),
                    'weight_of_value' => $this->getWeight(getLetterGrade($final_score)),
                ]);

                $this->updateGPA($result->student_id);
            });

            DB::commit();

            flashMessage('Berhasil melakukan perubahan');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        } catch (Throwable $e) {
            DB::rollBack();
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        }
    }
}
