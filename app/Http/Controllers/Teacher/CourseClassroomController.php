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
                'title' => "Kelas {$classroom->name} - {$course->name}",
                'subtitle' => 'Menampilkan data mahasiswa',
                'method' => 'PUT',
                'action' => route('teachers.classrooms.sync', [$course, $classroom]),
            ],
            'course' => $course,
            'classroom' => $classroom,
            'students' => CourseStudentClassroomResource::collection($students),
            'state' => [
                'search' => request()->search ?? '',
            ],
        ]);
    }

    /**
     * Calculate GPA for a student
     * 
     * @param int $studentId
     * @return float
     */
    private function calculateGPA(int $studentId): float
    {
        $student = Student::find($studentId);

        if (!$student) {
            Log::error("Student not found for ID: $studentId");
            return 0;
        }

        $studyResult = StudyResult::where('student_id', $student->id)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', $student->semester)
            ->first();

        if (!$studyResult) {
            Log::error("Study result not found for student ID: $studentId");
            return 0;
        }

        $studyResultGrades = StudyResultGrade::where('study_result_id', $studyResult->id)
            ->with('course')
            ->get();

        $totalScore = 0;
        $totalWeight = 0;

        foreach ($studyResultGrades as $grade) {
            $course = $grade->course;

            if (!$course || $course->credit <= 0) {
                continue;
            }

            $sks = $course->credit;
            $finalScore = min($grade->grade, 100);
            $gpaScore = $this->convertScoreToGPA($finalScore);

            $totalScore += $gpaScore * $sks;
            $totalWeight += $sks;
        }

        if ($totalWeight <= 0) {
            return 0;
        }

        return min(round($totalScore / $totalWeight, 2), 4);
    }

    /**
     * Update GPA for a student
     * 
     * @param int $studentId
     * @return void
     */
    private function updateGPA(int $studentId): void
    {
        $gpa = $this->calculateGPA($studentId);

        StudyResult::where('student_id', $studentId)
            ->where('academic_year_id', activeAcademicYear()->id)
            ->where('semester', function ($query) use ($studentId) {
                $query->select('semester')
                    ->from('students')
                    ->where('id', $studentId);
            })
            ->update(['gpa' => $gpa]);
    }

    /**
     * Sync attendance and grades, then calculate final scores
     * 
     * @param Course $course
     * @param Classroom $classroom
     * @param CourseClassroomRequest $request
     * @return RedirectResponse
     */
    public function sync(Course $course, Classroom $classroom, CourseClassroomRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            // Process attendances
            $attendances = [];
            foreach ($request->attendances as $attendance) {
                $attendances[] = array_merge($attendance, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!empty($attendances)) {
                Attendance::insert($attendances);
            }

            // Process grades
            foreach ($request->grades as $gradeData) {
                $gradeData = array_merge($gradeData, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Grade::updateOrCreate(
                    [
                        'student_id' => $gradeData['student_id'],
                        'course_id' => $gradeData['course_id'],
                        'classroom_id' => $gradeData['classroom_id'],
                        'category' => $gradeData['category'],
                        'section' => $gradeData['section'],
                    ],
                    [
                        'grade' => $gradeData['grade'],
                        'updated_at' => now(),
                    ]
                );
            }

            // Get all affected students
            $studentIds = collect($request->attendances)
                ->pluck('student_id')
                ->merge(collect($request->grades)->pluck('student_id'))
                ->unique()
                ->values();

            // Calculate final scores for all affected students
            $this->calculateFinalScores($course, $classroom, $studentIds);

            DB::commit();

            flashMessage('Berhasil menyimpan perubahan nilai');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error("Error syncing grades: " . $e->getMessage());
            flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        }
    }

    /**
     * Calculate final scores for students
     * 
     * @param Course $course
     * @param Classroom $classroom
     * @param Collection $studentIds
     * @return void
     */
    private function calculateFinalScores(Course $course, Classroom $classroom, $studentIds): void
    {
        $studyResults = StudyResult::whereIn('student_id', $studentIds)->get();

        foreach ($studyResults as $result) {
            $final_score = $this->calculateFinalScore(
                attendancePercentage: $this->calculateAttendancePercentage(
                    $this->getAttendanceCount($result->student_id, $course->id, $classroom->id)
                ),
                taskPercentage: $this->calculateTaskPercentage(
                    $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'tugas')
                ),
                utsPercentage: $this->calculateUTSPercentage(
                    $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'uts')
                ),
                uasPercentage: $this->calculateUASPercentage(
                    $this->getGradeCount($result->student_id, $course->id, $classroom->id, 'uas')
                )
            );

            // Update study result grade
            StudyResultGrade::updateOrCreate(
                [
                    'study_result_id' => $result->id,
                    'course_id' => $course->id,
                ],
                [
                    'grade' => $final_score,
                    'letter' => getLetterGrade($final_score),
                    'weight_of_value' => $this->getWeight(getLetterGrade($final_score)),
                ]
            );

            // Update student GPA
            $this->updateGPA($result->student_id);
        }
    }
}
