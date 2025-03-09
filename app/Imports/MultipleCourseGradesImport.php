<?php

namespace App\Imports;

use App\Models\Grade;
use App\Models\Course;
use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MultipleCourseGradesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
  use Importable;

  protected $classroomId;
  protected $courseMapping = [];
  protected $studentMapping = [];
  public $importResults = [];

  public function __construct($classroomId)
  {
    $this->classroomId = $classroomId;
    $this->importResults = [
      'success' => 0,
      'skipped' => 0,
      'updated' => 0,
      'error' => 0,
    ];

    // Load all courses for this classroom and create mapping kode_mk => course_id
    $courses = Course::select('id', 'course_code')
      ->whereHas('classrooms', function ($query) use ($classroomId) {
        $query->where('classrooms.id', $classroomId);
      })
      ->get();

    foreach ($courses as $course) {
      $this->courseMapping[$course->course_code] = $course->id;
    }

    // Load all students for this classroom and create mapping nim => student_id
    $students = Student::where('classroom_id', $classroomId)
      ->select('id', 'student_number')
      ->get();

    foreach ($students as $student) {
      $this->studentMapping[$student->student_number] = $student->id;
    }

    Log::info('MultipleCourseGradesImport initialized', [
      'classroom_id' => $classroomId,
      'courses' => count($this->courseMapping),
      'students' => count($this->studentMapping)
    ]);
  }

  public function collection(Collection $rows)
  {
    Log::info('Starting multi-course import with ' . count($rows) . ' rows');

    // Use transaction for better performance and data integrity
    DB::beginTransaction();

    try {
      foreach ($rows as $row) {
        if (empty($row['nim']) || empty($row['kode_mk'])) {
          $this->importResults['skipped']++;
          Log::info('Skipping row with missing nim or kode_mk', ['row' => $row]);
          continue;
        }

        // Get student_id from student_number (nim)
        $studentId = $this->studentMapping[$row['nim']] ?? null;
        if (!$studentId) {
          $this->importResults['skipped']++;
          Log::warning('Student not found', ['nim' => $row['nim']]);
          continue;
        }

        // Get course_id from course_code (kode_mk)
        $courseId = $this->courseMapping[$row['kode_mk']] ?? null;
        if (!$courseId) {
          $this->importResults['skipped']++;
          Log::warning('Course not found', ['kode_mk' => $row['kode_mk']]);
          continue;
        }

        // Process tugas grade
        if (!empty($row['nilai_tugas']) && is_numeric($row['nilai_tugas'])) {
          $this->processGrade($studentId, $courseId, 'tugas', $row['nilai_tugas']);
        }

        // Process UTS grade
        if (!empty($row['nilai_uts']) && is_numeric($row['nilai_uts'])) {
          $this->processGrade($studentId, $courseId, 'uts', $row['nilai_uts']);
        }

        // Process UAS grade
        if (!empty($row['nilai_uas']) && is_numeric($row['nilai_uas'])) {
          $this->processGrade($studentId, $courseId, 'uas', $row['nilai_uas']);
        }
      }

      DB::commit();
      Log::info('Multi-course import completed successfully', $this->importResults);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error in multi-course import: ' . $e->getMessage());
      Log::error($e->getTraceAsString());
      throw $e;
    }
  }

  protected function processGrade($studentId, $courseId, $category, $value)
  {
    try {
      // Round the value
      $roundedValue = round((float)$value);

      // Check if grade already exists
      $existingGrade = Grade::where([
        'student_id' => $studentId,
        'course_id' => $courseId,
        'classroom_id' => $this->classroomId,
        'category' => $category,
      ])->whereNull('section')->first();

      if ($existingGrade) {
        // Update existing grade
        $existingGrade->grade = $roundedValue;
        $existingGrade->save();
        $this->importResults['updated']++;

        Log::info("Updated grade: student[$studentId] course[$courseId] $category = $roundedValue");
      } else {
        // Create new grade
        $grade = new Grade();
        $grade->student_id = $studentId;
        $grade->course_id = $courseId;
        $grade->classroom_id = $this->classroomId;
        $grade->grade = $roundedValue;
        $grade->category = $category;
        $grade->section = null;
        $grade->save();
        $this->importResults['success']++;

        Log::info("Created grade: student[$studentId] course[$courseId] $category = $roundedValue");
      }

      return true;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing grade: student[$studentId] course[$courseId] $category = $value");
      Log::error($e->getMessage());
      return false;
    }
  }

  public function rules(): array
  {
    return [
      'nim' => 'nullable',
      'kode_mk' => 'nullable',
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
