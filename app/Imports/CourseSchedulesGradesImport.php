<?php

namespace App\Imports;

use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Student;
use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseSchedulesGradesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
  use Importable;

  protected $courseId;
  protected $course;
  protected $classroomIds = [];
  protected $studentMapping = [];
  public $importResults = [];

  public function __construct($courseId)
  {
    $this->courseId = $courseId;
    $this->course = Course::find($courseId);

    if (!$this->course) {
      throw new \Exception("Course with ID $courseId not found");
    }

    $this->importResults = [
      'grades_success' => 0,
      'grades_updated' => 0,
      'grades_skipped' => 0,
      'attendance_success' => 0,
      'course_mismatch' => 0, // Counter khusus untuk baris yang tidak cocok course ID-nya
      'rows_processed' => 0,
      'error' => 0,
      'details' => []
    ];

    // Dapatkan semua jadwal untuk mata kuliah ini
    $schedules = Schedule::where('course_id', $courseId)
      ->select('id', 'classroom_id')
      ->get();

    // Kumpulkan semua classroom_id dari jadwal
    $this->classroomIds = $schedules->pluck('classroom_id')->unique()->toArray();

    // Verbose logging untuk debugging
    Log::info('CourseSchedulesGradesImport initialized', [
      'course_id' => $courseId,
      'course_name' => $this->course->name,
      'found_classrooms' => count($this->classroomIds),
      'classroom_ids' => $this->classroomIds
    ]);

    // Load semua mahasiswa untuk kelas-kelas ini
    foreach ($this->classroomIds as $classroomId) {
      $students = Student::where('classroom_id', $classroomId)
        ->select('id', 'student_number', 'classroom_id')
        ->get();

      Log::info("Loaded students for classroom $classroomId", [
        'count' => $students->count()
      ]);

      // Buat mapping untuk lookup cepat
      foreach ($students as $student) {
        // Kunci gabungan untuk lookup lebih akurat
        $key = $student->student_number . '_' . $student->classroom_id;
        $this->studentMapping[$key] = [
          'id' => $student->id,
          'classroom_id' => $student->classroom_id,
          'nim' => $student->student_number
        ];
      }
    }

    Log::info('Student mapping created', [
      'total_mappings' => count($this->studentMapping)
    ]);
  }

  public function collection(Collection $rows)
  {
    Log::info('Starting course schedules import with ' . count($rows) . ' rows');

    // Use transaction for data integrity
    DB::beginTransaction();

    try {
      foreach ($rows as $index => $row) {
        $this->importResults['rows_processed']++;

        // Skip pembatas baris (biasanya baris kosong/pemisah antar kelas)
        if (empty($row['nim']) || empty($row['classroom_id'])) {
          Log::info("Skipping separator row at index $index");
          continue;
        }

        // VALIDASI: Verifikasi course_id 
        if (isset($row['course_id']) && $row['course_id'] !== '') {
          $excelCourseId = (string)$row['course_id']; // Konversi ke string untuk perbandingan yang aman
          $currentCourseId = (string)$this->courseId;

          // Validasi yang lebih ketat: course_id harus sama persis
          if ($excelCourseId !== $currentCourseId) {
            $this->importResults['course_mismatch']++;
            Log::info("Skipping row due to course ID mismatch", [
              'row_index' => $index,
              'excel_course_id' => $excelCourseId,
              'current_course_id' => $currentCourseId,
              'nim' => $row['nim']
            ]);
            continue;
          }
        } else {
          // Jika tidak ada course_id, gunakan fallback ke validasi nama mata kuliah
          // (untuk kompatibilitas dengan template lama tanpa course_id)
          if (isset($row['nama_mata_kuliah']) && $row['nama_mata_kuliah'] !== '') {
            // Normalisasi nama mata kuliah untuk perbandingan
            $excelCourseName = trim(strtolower($row['nama_mata_kuliah']));
            $currentCourseName = trim(strtolower($this->course->name));

            // Cari kesamaan parsial
            if (
              strpos($excelCourseName, $currentCourseName) === false &&
              strpos($currentCourseName, $excelCourseName) === false
            ) {
              $this->importResults['course_mismatch']++;
              Log::info("Skipping row due to course name mismatch (fallback validation)", [
                'row_index' => $index,
                'excel_course' => $row['nama_mata_kuliah'],
                'current_course' => $this->course->name,
                'nim' => $row['nim']
              ]);
              continue;
            }
          }
        }

        // Debug data row untuk memastikan format benar
        Log::info("Processing row $index", [
          'nim' => $row['nim'],
          'classroom_id' => $row['classroom_id'],
          'course_id' => $row['course_id'] ?? 'not set',
          'mata_kuliah' => $row['nama_mata_kuliah'] ?? 'not set',
          'has_tugas' => isset($row['nilai_tugas']) && $row['nilai_tugas'] !== '' && $row['nilai_tugas'] !== null
        ]);

        // Verifikasi classroom_id ada dalam daftar kelas untuk mata kuliah ini
        if (!in_array($row['classroom_id'], $this->classroomIds)) {
          $this->importResults['grades_skipped']++;
          Log::warning("Classroom not related to this course", [
            'classroom_id' => $row['classroom_id'],
            'available_classrooms' => $this->classroomIds
          ]);
          continue;
        }

        // Get student_id dari student_number (nim) dan classroom_id
        $lookupKey = $row['nim'] . '_' . $row['classroom_id'];
        $studentData = $this->studentMapping[$lookupKey] ?? null;

        if (!$studentData) {
          $this->importResults['grades_skipped']++;
          Log::warning("Student not found", [
            'lookup_key' => $lookupKey,
            'nim' => $row['nim'],
            'classroom_id' => $row['classroom_id']
          ]);
          continue;
        }

        $studentId = $studentData['id'];
        $classroomId = $studentData['classroom_id'];

        // 1. Proses absensi jika ada
        if (isset($row['nilai_absensi']) && $row['nilai_absensi'] !== '' && $row['nilai_absensi'] !== null) {
          $this->processAttendance($studentId, $classroomId, (int)$row['nilai_absensi'], $index);
        }

        // 2. Proses nilai tugas
        if (isset($row['nilai_tugas']) && $row['nilai_tugas'] !== '' && $row['nilai_tugas'] !== null) {
          $this->processGrade($studentId, $classroomId, 'tugas', $row['nilai_tugas'], $index);
        }

        // 3. Proses nilai UTS
        if (isset($row['nilai_uts']) && $row['nilai_uts'] !== '' && $row['nilai_uts'] !== null) {
          $this->processGrade($studentId, $classroomId, 'uts', $row['nilai_uts'], $index);
        }

        // 4. Proses nilai UAS
        if (isset($row['nilai_uas']) && $row['nilai_uas'] !== '' && $row['nilai_uas'] !== null) {
          $this->processGrade($studentId, $classroomId, 'uas', $row['nilai_uas'], $index);
        }
      }

      DB::commit();
      Log::info('Course schedules import completed successfully', $this->importResults);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error in course schedules import: ' . $e->getMessage());
      Log::error($e->getTraceAsString());

      $this->importResults['error']++;
      $this->importResults['exception'] = $e->getMessage();

      throw $e;
    }
  }

  protected function processGrade($studentId, $classroomId, $category, $value, $rowIndex)
  {
    try {
      // Pastikan nilai valid dan dalam rentang
      if (!is_numeric($value)) {
        $this->importResults['grades_skipped']++;
        Log::warning("Non-numeric value", ['category' => $category, 'value' => $value]);
        return false;
      }

      $roundedValue = round((float)$value);

      if ($roundedValue < 0 || $roundedValue > 100) {
        $this->importResults['grades_skipped']++;
        Log::warning("Value out of range", ['category' => $category, 'value' => $value]);
        return false;
      }

      // Cek apakah grade sudah ada
      $existingGrade = Grade::where([
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'category' => $category,
      ])->whereNull('section')->first();

      if ($existingGrade) {
        // Update nilai yang sudah ada
        $oldValue = $existingGrade->grade;
        $existingGrade->grade = $roundedValue;
        $result = $existingGrade->save();

        if ($result) {
          $this->importResults['grades_updated']++;
          Log::info("Updated grade", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'category' => $category,
            'old_value' => $oldValue,
            'new_value' => $roundedValue
          ]);
        } else {
          $this->importResults['error']++;
          Log::error("Failed to update grade", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'category' => $category
          ]);
          return false;
        }
      } else {
        // Buat grade baru
        $grade = new Grade();
        $grade->student_id = $studentId;
        $grade->course_id = $this->courseId;
        $grade->classroom_id = $classroomId;
        $grade->grade = $roundedValue;
        $grade->category = $category;
        $grade->section = null;
        $result = $grade->save();

        if ($result) {
          $this->importResults['grades_success']++;
          Log::info("Created grade", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'category' => $category,
            'value' => $roundedValue
          ]);
        } else {
          $this->importResults['error']++;
          Log::error("Failed to create grade", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'category' => $category
          ]);
          return false;
        }
      }

      return true;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing grade", [
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'category' => $category,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  protected function processAttendance($studentId, $classroomId, $totalMeetings, $rowIndex)
  {
    try {
      // Validasi nilai absensi
      if (!is_numeric($totalMeetings) || $totalMeetings < 0 || $totalMeetings > 16) {
        Log::warning("Invalid attendance value", [
          'student_id' => $studentId,
          'value' => $totalMeetings
        ]);
        return false;
      }

      $totalMeetings = (int)$totalMeetings;

      // Hapus data absensi yang sudah ada untuk mata kuliah ini
      Attendance::where([
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
      ])->delete();

      // Buat absensi baru untuk setiap pertemuan yang dihadiri
      $attendanceCreated = 0;

      for ($section = 1; $section <= $totalMeetings; $section++) {
        $attendance = new Attendance();
        $attendance->student_id = $studentId;
        $attendance->course_id = $this->courseId;
        $attendance->classroom_id = $classroomId;
        $attendance->status = true; // Hadir
        $attendance->section = $section;

        if ($attendance->save()) {
          $attendanceCreated++;
        }
      }

      $this->importResults['attendance_success'] += $attendanceCreated;

      Log::info("Created attendance records", [
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'total_meetings' => $totalMeetings,
        'records_created' => $attendanceCreated
      ]);

      return $attendanceCreated > 0;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing attendance", [
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  public function rules(): array
  {
    return [
      'nim' => 'nullable',
      'classroom_id' => 'nullable|numeric',
      'course_id' => 'nullable', // Validasi untuk kolom course_id
      'nama_mata_kuliah' => 'nullable',
      'nilai_absensi' => 'nullable|integer|min:0|max:16',
      'nilai_tugas' => 'nullable|numeric|min:0|max:100',
      'nilai_uts' => 'nullable|numeric|min:0|max:100',
      'nilai_uas' => 'nullable|numeric|min:0|max:100',
    ];
  }

  public function getImportResults()
  {
    return $this->importResults;
  }
}
