<?php

namespace App\Imports;

use App\Models\Attendance;
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

class CourseSchedulesAttendancesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
  use Importable;

  protected $courseId;
  protected $classroomIds = [];
  protected $studentMapping = [];
  protected $totalMeetings = 16;
  public $importResults = [];

  public function __construct($courseId, $totalMeetings = 16)
  {
    $this->courseId = $courseId;
    $this->totalMeetings = $totalMeetings;
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

    // Log untuk debugging
    Log::info('CourseSchedulesAttendancesImport initialized', [
      'course_id' => $courseId,
      'found_classrooms' => count($this->classroomIds),
      'classroom_ids' => $this->classroomIds,
      'total_meetings' => $this->totalMeetings
    ]);

    // Load semua mahasiswa untuk kelas-kelas ini
    foreach ($this->classroomIds as $classroomId) {
      $students = Student::where('classroom_id', $classroomId)
        ->select('id', 'student_number', 'classroom_id')
        ->get();

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
  }

  public function collection(Collection $rows)
  {
    Log::info('Starting course schedules attendances import with ' . count($rows) . ' rows');

    // Use transaction for data integrity
    DB::beginTransaction();

    try {
      foreach ($rows as $index => $row) {
        $this->importResults['rows_processed']++;

        // Skip pembatas baris atau baris kosong
        if (empty($row['nim']) || empty($row['classroom_id'])) {
          $this->importResults['skipped']++;
          continue;
        }

        // Verifikasi classroom_id ada dalam daftar kelas untuk mata kuliah ini
        if (!in_array($row['classroom_id'], $this->classroomIds)) {
          $this->importResults['skipped']++;
          Log::warning("Classroom not related to this course", [
            'classroom_id' => $row['classroom_id']
          ]);
          continue;
        }

        // Get student_id dari student_number (nim) dan classroom_id
        $lookupKey = $row['nim'] . '_' . $row['classroom_id'];
        $studentData = $this->studentMapping[$lookupKey] ?? null;

        if (!$studentData) {
          $this->importResults['skipped']++;
          Log::warning("Student not found", [
            'nim' => $row['nim'],
            'classroom_id' => $row['classroom_id']
          ]);
          continue;
        }

        $studentId = $studentData['id'];
        $classroomId = $studentData['classroom_id'];

        // Hitung berapa pertemuan yang diproses dari baris ini
        $meetingsProcessed = 0;

        // Proses absensi untuk setiap pertemuan
        for ($i = 1; $i <= $this->totalMeetings; $i++) {
          $key = 'pertemuan_' . $i;

          if (!isset($row[$key]) || ($row[$key] !== '1' && $row[$key] !== 1)) {
            continue;
          }

          $result = $this->processAttendance($studentId, $classroomId, $i);
          if ($result) $meetingsProcessed++;
        }

        // Log hasil
        if ($meetingsProcessed > 0) {
          Log::info("Processed attendance for student", [
            'student_id' => $studentId,
            'meetings_processed' => $meetingsProcessed
          ]);
        }
      }

      DB::commit();
      Log::info('Course schedules attendances import completed successfully', $this->importResults);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error in course schedules attendances import: ' . $e->getMessage());
      Log::error($e->getTraceAsString());

      $this->importResults['error']++;
      $this->importResults['exception'] = $e->getMessage();

      throw $e;
    }
  }

  protected function processAttendance($studentId, $classroomId, $section)
  {
    try {
      // Cek apakah sudah ada data absensi
      $existingAttendance = Attendance::where([
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'section' => $section
      ])->first();

      if ($existingAttendance) {
        // Absensi sudah ada, tidak perlu update karena status sudah hadir
        $this->importResults['skipped']++;
        return true;
      } else {
        // Buat absensi baru
        $attendance = new Attendance();
        $attendance->student_id = $studentId;
        $attendance->course_id = $this->courseId;
        $attendance->classroom_id = $classroomId;
        $attendance->status = true; // Hadir
        $attendance->section = $section;
        $result = $attendance->save();

        if ($result) {
          $this->importResults['success']++;
          Log::info("Created attendance", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'section' => $section
          ]);
        } else {
          $this->importResults['error']++;
          Log::error("Failed to create attendance", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $classroomId,
            'section' => $section
          ]);
          return false;
        }
      }

      return true;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing attendance", [
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $classroomId,
        'section' => $section,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  public function rules(): array
  {
    $rules = [
      'nim' => 'nullable',
      'classroom_id' => 'nullable|numeric',
    ];

    for ($i = 1; $i <= $this->totalMeetings; $i++) {
      $rules['pertemuan_' . $i] = 'nullable|in:0,1';
    }

    return $rules;
  }

  public function getImportResults()
  {
    return $this->importResults;
  }
}
