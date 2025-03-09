<?php

namespace App\Imports;

use App\Models\Grade;
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
  protected $classroomIds = [];
  protected $studentMapping = [];
  public $importResults = [];

  public function __construct($courseId)
  {
    $this->courseId = $courseId;
    $this->importResults = [
      'success' => 0,
      'skipped' => 0,
      'updated' => 0,
      'error' => 0,
      'rows_processed' => 0,
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
      'found_classrooms' => count($this->classroomIds),
      'classroom_ids' => $this->classroomIds
    ]);

    // Load semua mahasiswa untuk kelas-kelas ini
    foreach ($this->classroomIds as $classroomId) {
      $students = Student::where('classroom_id', $classroomId)
        ->select('id', 'student_number', 'classroom_id')
        ->get();

      Log::info("Loaded students for classroom $classroomId", [
        'count' => $students->count(),
        'first_few' => $students->take(3)->pluck('student_number')->toArray()
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
      'total_mappings' => count($this->studentMapping),
      'sample_keys' => array_slice(array_keys($this->studentMapping), 0, 3)
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
          $this->importResults['skipped']++;
          $this->importResults['details'][] = [
            'row' => $index + 2, // +2 karena Excel dimulai dari 1 dan headingRow
            'status' => 'skipped',
            'reason' => 'Empty NIM or classroom_id',
            'data' => json_encode(array_filter((array)$row))
          ];
          continue;
        }

        // Debug data row untuk memastikan format benar
        Log::info("Processing row $index", [
          'nim' => $row['nim'],
          'classroom_id' => $row['classroom_id'],
          'has_tugas' => isset($row['nilai_tugas']) && $row['nilai_tugas'] !== '' && $row['nilai_tugas'] !== null,
          'has_uts' => isset($row['nilai_uts']) && $row['nilai_uts'] !== '' && $row['nilai_uts'] !== null,
          'has_uas' => isset($row['nilai_uas']) && $row['nilai_uas'] !== '' && $row['nilai_uas'] !== null,
        ]);

        // Verifikasi classroom_id ada dalam daftar kelas untuk mata kuliah ini
        if (!in_array($row['classroom_id'], $this->classroomIds)) {
          $this->importResults['skipped']++;
          $this->importResults['details'][] = [
            'row' => $index + 2,
            'status' => 'skipped',
            'reason' => "Classroom ID {$row['classroom_id']} not related to this course",
            'data' => json_encode(['nim' => $row['nim'], 'classroom_id' => $row['classroom_id']])
          ];
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
          $this->importResults['skipped']++;
          $this->importResults['details'][] = [
            'row' => $index + 2,
            'status' => 'skipped',
            'reason' => "Student with NIM {$row['nim']} not found in classroom {$row['classroom_id']}",
            'data' => json_encode(['nim' => $row['nim'], 'classroom_id' => $row['classroom_id']])
          ];
          Log::warning("Student not found", [
            'lookup_key' => $lookupKey,
            'nim' => $row['nim'],
            'classroom_id' => $row['classroom_id'],
            'available_mappings_sample' => array_slice(array_keys($this->studentMapping), 0, 5)
          ]);
          continue;
        }

        $studentId = $studentData['id'];
        $classroomId = $studentData['classroom_id'];

        // Hitung berapa nilai yang diproses dari baris ini
        $valuesProcessed = 0;

        // Proses nilai tugas
        if (isset($row['nilai_tugas']) && $row['nilai_tugas'] !== '' && $row['nilai_tugas'] !== null) {
          $result = $this->processGrade($studentId, $classroomId, 'tugas', $row['nilai_tugas'], $index);
          if ($result) $valuesProcessed++;
        }

        // Proses nilai UTS
        if (isset($row['nilai_uts']) && $row['nilai_uts'] !== '' && $row['nilai_uts'] !== null) {
          $result = $this->processGrade($studentId, $classroomId, 'uts', $row['nilai_uts'], $index);
          if ($result) $valuesProcessed++;
        }

        // Proses nilai UAS
        if (isset($row['nilai_uas']) && $row['nilai_uas'] !== '' && $row['nilai_uas'] !== null) {
          $result = $this->processGrade($studentId, $classroomId, 'uas', $row['nilai_uas'], $index);
          if ($result) $valuesProcessed++;
        }

        // Jika tidak ada nilai yang diproses, catat sebagai dilewati
        if ($valuesProcessed === 0) {
          $this->importResults['skipped']++;
          $this->importResults['details'][] = [
            'row' => $index + 2,
            'status' => 'skipped',
            'reason' => "No valid grades found",
            'data' => json_encode(['nim' => $row['nim'], 'classroom_id' => $row['classroom_id']])
          ];
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
        $this->importResults['skipped']++;
        $this->importResults['details'][] = [
          'row' => $rowIndex + 2,
          'status' => 'invalid',
          'reason' => "Non-numeric value for $category: '$value'",
          'data' => json_encode(['student_id' => $studentId, 'classroom_id' => $classroomId, 'category' => $category])
        ];
        Log::warning("Non-numeric value", ['category' => $category, 'value' => $value]);
        return false;
      }

      $roundedValue = round((float)$value);

      if ($roundedValue < 0 || $roundedValue > 100) {
        $this->importResults['skipped']++;
        $this->importResults['details'][] = [
          'row' => $rowIndex + 2,
          'status' => 'invalid',
          'reason' => "Value out of range (0-100) for $category: '$value'",
          'data' => json_encode(['student_id' => $studentId, 'classroom_id' => $classroomId, 'category' => $category])
        ];
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
          $this->importResults['updated']++;
          $this->importResults['details'][] = [
            'row' => $rowIndex + 2,
            'status' => 'updated',
            'info' => "Updated $category: $oldValue → $roundedValue",
            'data' => json_encode([
              'student_id' => $studentId,
              'course_id' => $this->courseId,
              'classroom_id' => $classroomId,
              'category' => $category
            ])
          ];
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
          $this->importResults['success']++;
          $this->importResults['details'][] = [
            'row' => $rowIndex + 2,
            'status' => 'created',
            'info' => "Created new $category grade: $roundedValue",
            'data' => json_encode([
              'student_id' => $studentId,
              'course_id' => $this->courseId,
              'classroom_id' => $classroomId,
              'category' => $category
            ])
          ];
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
      $this->importResults['details'][] = [
        'row' => $rowIndex + 2,
        'status' => 'error',
        'reason' => $e->getMessage(),
        'data' => json_encode([
          'student_id' => $studentId,
          'course_id' => $this->courseId,
          'classroom_id' => $classroomId,
          'category' => $category
        ])
      ];
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

  public function rules(): array
  {
    return [
      'nim' => 'nullable',
      'classroom_id' => 'nullable|numeric',
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
