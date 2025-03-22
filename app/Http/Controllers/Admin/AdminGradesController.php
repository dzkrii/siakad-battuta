<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudyResult;
use App\Models\StudyResultGrade;
use App\Models\Course;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Traits\CalculatesFinalScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Enums\MessageType;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Schedule;
use Illuminate\Support\Collection;

class AdminGradesController extends Controller
{
  use CalculatesFinalScore;

  /**
   * Show semester selection for student grades editing
   * 
   * @param Student $student
   * @return \Inertia\Response
   */
  public function selectSemester(Student $student)
  {
    // Get all study results for this student
    $studyResults = StudyResult::where('student_id', $student->id)
      ->with('academicYear')
      ->withCount(['studyResultGrades as total_credit' => function ($query) {
        $query->join('courses', 'study_result_grades.course_id', '=', 'courses.id')
          ->select(DB::raw('SUM(courses.credit)'));
      }])
      ->orderBy('semester', 'asc')
      ->get();

    // Get available semesters
    $semesters = range(1, $student->semester);

    return Inertia::render('Admin/Students/Grades/SelectSemester', [
      'page_settings' => [
        'title' => "Edit Nilai Mahasiswa: {$student->user->name}",
        'subtitle' => "NIM: {$student->student_number}",
      ],
      'student' => $student->load('user', 'faculty', 'department', 'classroom'),
      'studyResults' => $studyResults,
      'semesters' => $semesters
    ]);
  }

  /**
   * Show the grades edit form for a specific semester
   * 
   * @param Student $student
   * @param int $semester
   * @return \Inertia\Response
   */
  public function edit(Student $student, int $semester)
  {
    // Get study result for the specified semester
    $studyResult = StudyResult::where('student_id', $student->id)
      ->where('semester', $semester)
      ->with('academicYear')
      ->first();

    if (!$studyResult) {
      // Instead of creating a new study result, redirect back with a message
      flashMessage('Data nilai untuk semester ini belum tersedia', 'warning');
      return redirect()->route('admin.students.grades.select-semester', $student);
    }

    // DEBUGGING: Untuk melihat query SQL yang dijalankan
    DB::enableQueryLog();

    // Get courses dari study plans (approved) dan dari study result grades
    $studyPlanCourseIds = Course::query()
      ->select('courses.id')
      ->join('schedules', 'courses.id', '=', 'schedules.course_id')
      ->join('study_plan_schedule', 'schedules.id', '=', 'study_plan_schedule.schedule_id')
      ->join('study_plans', 'study_plan_schedule.study_plan_id', '=', 'study_plans.id')
      ->where('study_plans.student_id', $student->id)
      ->where('study_plans.semester', $semester)
      ->where('study_plans.status', 'Approved')
      ->where('courses.semester', $semester)
      ->pluck('courses.id')
      ->toArray();

    $gradeCourseIds = StudyResultGrade::where('study_result_id', $studyResult->id)
      ->pluck('course_id')
      ->toArray();

    // Gabungkan ID course dari kedua sumber
    $allCourseIds = array_unique(array_merge($studyPlanCourseIds, $gradeCourseIds));

    // DEBUGGING: Lihat semua course ID yang ditemukan
    Log::info("Student ID: {$student->id}, Semester: {$semester}");
    Log::info("StudyPlan Course IDs: " . implode(', ', $studyPlanCourseIds));
    Log::info("Grade Course IDs: " . implode(', ', $gradeCourseIds));
    Log::info("All Course IDs: " . implode(', ', $allCourseIds));

    // Dapatkan semua course berdasarkan ID
    $courses = Course::whereIn('id', $allCourseIds)->get();

    // DEBUGGING: Query log untuk melihat SQL yang dijalankan
    Log::info(DB::getQueryLog());

    // DEBUGGING: Lihat course yang ditemukan
    foreach ($courses as $index => $course) {
      Log::info("Course {$index}: ID={$course->id}, Name={$course->name}, Code={$course->kode_matkul}");
    }

    // For each course, get the existing grades
    $courseGrades = [];

    foreach ($courses as $course) {
      // PENTING: Tangani kasus jika classroom_id tidak ditemukan
      try {
        // Get the classroom for this course (using the most recent schedule)
        $schedule = Schedule::where('course_id', $course->id)
          ->orderBy('created_at', 'desc')
          ->first();

        $classroomId = $schedule ? $schedule->classroom_id : null;

        // JIKA TIDAK ADA CLASSROOM, GUNAKAN DEFAULT CLASSROOM ID = 1
        // Atau gunakan ID classroom lain yang valid di sistem Anda
        if ($classroomId === null) {
          Log::warning("Classroom ID not found for course ID: {$course->id}, Name: {$course->name}. Using default classroom.");
          $classroomId = 1; // Ganti dengan classroom ID default yang valid
        }

        // Try to find the grades for this course
        $grades = [
          'tugas' => $this->getGrade($student->id, $course->id, $classroomId, 'tugas'),
          'uts' => $this->getGrade($student->id, $course->id, $classroomId, 'uts'),
          'uas' => $this->getGrade($student->id, $course->id, $classroomId, 'uas')
        ];

        // Get attendance count
        $attendanceCount = Attendance::where('student_id', $student->id)
          ->where('course_id', $course->id)
          ->where('classroom_id', $classroomId)
          ->whereNotNull('status')
          ->where('status', 1)
          ->count();

        // Get the study result grade if exists
        $studyResultGrade = StudyResultGrade::where('study_result_id', $studyResult->id)
          ->where('course_id', $course->id)
          ->first();

        $courseGrades[] = [
          'course' => $course,
          'classroom_id' => $classroomId,
          'grades' => $grades,
          'attendance_count' => $attendanceCount,
          'final_score' => $studyResultGrade ? $studyResultGrade->grade : null,
          'letter' => $studyResultGrade ? $studyResultGrade->letter : null
        ];

        // DEBUGGING: Log setiap course yang berhasil diproses
        Log::info("Successfully processed course: {$course->name} with classroom ID: {$classroomId}");
      } catch (\Exception $e) {
        // TANGANI ERROR: Log error dan tetap tambahkan course ke daftar dengan nilai default
        Log::error("Error processing course ID: {$course->id}, Name: {$course->name}. Error: {$e->getMessage()}");

        $courseGrades[] = [
          'course' => $course,
          'classroom_id' => 1, // Gunakan classroom default
          'grades' => [
            'tugas' => 0,
            'uts' => 0,
            'uas' => 0
          ],
          'attendance_count' => 0,
          'final_score' => 0,
          'letter' => 'E'
        ];
      }
    }

    // DEBUGGING: Pastikan jumlah course grades sama dengan jumlah courses
    Log::info("Total courses: " . count($courses) . ", Total course grades: " . count($courseGrades));

    return Inertia::render('Admin/Students/Grades/Edit', [
      'page_settings' => [
        'title' => "Edit Nilai Semester {$semester}",
        'subtitle' => "{$student->user->name} - {$student->student_number}",
        'method' => 'PUT',
        'action' => route('admin.students.grades.update', [$student, $semester]),
      ],
      'student' => $student->load('user', 'faculty', 'department', 'classroom'),
      'semester' => $semester,
      'studyResult' => $studyResult,
      'courseGrades' => $courseGrades,
    ]);
  }

  /**
   * Get grade value for a specific category
   *
   * @param int $studentId
   * @param int $courseId
   * @param int|null $classroomId
   * @param string $category
   * @return int
   */
  private function getGrade($studentId, $courseId, $classroomId, $category)
  {
    return Grade::where('student_id', $studentId)
      ->where('course_id', $courseId)
      ->where('classroom_id', $classroomId)
      ->where('category', $category)
      ->whereNull('section') // Main grades don't have sections
      ->value('grade') ?? 0;
  }

  /**
   * Update the student grades for a specific semester
   * 
   * @param Request $request
   * @param Student $student
   * @param int $semester
   * @return \Illuminate\Http\RedirectResponse
   */
  public function update(Request $request, Student $student, int $semester)
  {
    $request->validate([
      'grades' => 'required|array',
      'grades.*.course_id' => 'required|exists:courses,id',
      'grades.*.classroom_id' => 'required|exists:classrooms,id',
      'grades.*.tugas' => 'nullable|numeric|min:0|max:100',
      'grades.*.uts' => 'nullable|numeric|min:0|max:100',
      'grades.*.uas' => 'nullable|numeric|min:0|max:100',
      'grades.*.attendance_count' => 'nullable|numeric|min:0|max:16',
    ]);

    try {
      DB::beginTransaction();

      // Get study result for this semester
      $studyResult = StudyResult::where('student_id', $student->id)
        ->where('semester', $semester)
        ->first();

      if (!$studyResult) {
        // If study result doesn't exist, create a new one
        $academicYear = AcademicYear::where('is_active', 1)->first();

        // If no active academic year, use the latest one
        if (!$academicYear) {
          $academicYear = AcademicYear::orderBy('id', 'desc')->first();

          if (!$academicYear) {
            throw new \Exception("Tidak ada tahun akademik yang tersedia");
          }
        }

        $studyResult = new StudyResult();
        $studyResult->student_id = $student->id;
        $studyResult->academic_year_id = $academicYear->id;
        $studyResult->semester = $semester;
        // $studyResult->status = 'Aktif';
        $studyResult->save();
      }

      $totalCredits = 0;

      foreach ($request->grades as $gradeData) {
        $course = Course::find($gradeData['course_id']);
        if (!$course) {
          continue;
        }

        $totalCredits += $course->credit;
        $classroomId = $gradeData['classroom_id'];

        // Update/create grades in the grades table
        $this->updateComponentGrade($student->id, $course->id, $classroomId, 'tugas', $gradeData['tugas'] ?? 0);
        $this->updateComponentGrade($student->id, $course->id, $classroomId, 'uts', $gradeData['uts'] ?? 0);
        $this->updateComponentGrade($student->id, $course->id, $classroomId, 'uas', $gradeData['uas'] ?? 0);

        // Update attendance records
        $this->updateAttendanceRecords($student->id, $course->id, $classroomId, $gradeData['attendance_count'] ?? 0);

        // Calculate final score based on the provided component values
        $attendancePercentage = $this->calculateAttendancePercentage($gradeData['attendance_count'] ?? 0);
        $taskPercentage = $this->calculateTaskPercentage($gradeData['tugas'] ?? 0);
        $utsPercentage = $this->calculateUTSPercentage($gradeData['uts'] ?? 0);
        $uasPercentage = $this->calculateUASPercentage($gradeData['uas'] ?? 0);

        $finalScore = $this->calculateFinalScore(
          $attendancePercentage,
          $taskPercentage,
          $utsPercentage,
          $uasPercentage
        );

        // Update the study result grade
        $studyResultGrade = StudyResultGrade::where('study_result_id', $studyResult->id)
          ->where('course_id', $course->id)
          ->first();

        if (!$studyResultGrade) {
          $studyResultGrade = new StudyResultGrade();
          $studyResultGrade->study_result_id = $studyResult->id;
          $studyResultGrade->course_id = $course->id;
        }

        $studyResultGrade->grade = $finalScore;
        $studyResultGrade->letter = getLetterGrade($finalScore);
        $studyResultGrade->weight_of_value = $this->getWeight(getLetterGrade($finalScore));
        $studyResultGrade->save();
      }

      // Recalculate GPA
      $this->updateGPA($student->id, $semester);

      DB::commit();

      flashMessage('Berhasil memperbarui nilai mahasiswa');
      return to_route('admin.students.grades.edit', [$student, $semester]);
    } catch (\Throwable $e) {
      DB::rollBack();
      Log::error("Error updating student grades: " . $e->getMessage());

      flashMessage(MessageType::ERROR->message(error: $e->getMessage()), 'error');
      return back();
    }
  }

  /**
   * Update a component grade (tugas, uts, uas)
   *
   * @param int $studentId
   * @param int $courseId
   * @param int $classroomId
   * @param string $category
   * @param int $value
   * @return void
   */
  private function updateComponentGrade($studentId, $courseId, $classroomId, $category, $value)
  {
    Grade::updateOrCreate(
      [
        'student_id' => $studentId,
        'course_id' => $courseId,
        'classroom_id' => $classroomId,
        'category' => $category,
        'section' => null
      ],
      [
        'grade' => $value,
        'updated_at' => now()
      ]
    );
  }

  /**
   * Update attendance records to match the requested count
   *
   * @param int $studentId
   * @param int $courseId
   * @param int $classroomId
   * @param int $attendanceCount
   * @return void
   */
  private function updateAttendanceRecords($studentId, $courseId, $classroomId, $attendanceCount)
  {
    // First, delete existing attendance records
    Attendance::where('student_id', $studentId)
      ->where('course_id', $courseId)
      ->where('classroom_id', $classroomId)
      ->delete();

    // Create new attendance records
    for ($section = 1; $section <= min($attendanceCount, 16); $section++) {
      Attendance::create([
        'student_id' => $studentId,
        'course_id' => $courseId,
        'classroom_id' => $classroomId,
        'section' => $section,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  /**
   * Calculate GPA for a student
   * 
   * @param int $studentId
   * @param int $semester
   * @return float
   */
  private function calculateGPA(int $studentId, int $semester): float
  {
    $studyResult = StudyResult::where('student_id', $studentId)
      ->where('semester', $semester)
      ->first();

    if (!$studyResult) {
      Log::error("Study result not found for student ID: $studentId, semester: $semester");
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

    // Removed update of total_credit since the column doesn't exist
    // $studyResult->total_credit = $totalWeight;
    // $studyResult->save();

    return min(round($totalScore / $totalWeight, 2), 4);
  }

  /**
   * Update GPA for a student
   * 
   * @param int $studentId
   * @param int $semester
   * @return void
   */
  private function updateGPA(int $studentId, int $semester): void
  {
    $gpa = $this->calculateGPA($studentId, $semester);

    StudyResult::where('student_id', $studentId)
      ->where('semester', $semester)
      ->update(['gpa' => $gpa]);
  }
}
