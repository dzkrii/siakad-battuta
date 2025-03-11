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

class CustomDosenExcelImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
  use Importable;

  protected $courseId;
  protected $classroomId;
  protected $course;
  protected $studentMapping = [];
  public $importResults = [];

  // Konfigurasi mapping kolom Excel dosen ke kategori nilai
  protected $columnMappings = [
    'partisipatif' => 'nilai_absensi', // Partisipatif = nilai absensi
    'proyek' => 'tugas',                // Proyek = nilai tugas
    'uts' => 'uts',                     // UTS
    'uas' => 'uas'                      // UAS
  ];

  public function __construct($courseId, $classroomId)
  {
    $this->courseId = $courseId;
    $this->classroomId = $classroomId;
    $this->course = Course::find($courseId);

    if (!$this->course) {
      throw new \Exception("Course with ID $courseId not found");
    }

    $this->importResults = [
      'grades_success' => 0,
      'grades_updated' => 0,
      'grades_skipped' => 0,
      'attendance_success' => 0,
      'students_not_found' => 0,
      'rows_processed' => 0,
      'error' => 0,
      'details' => []
    ];

    // Load semua mahasiswa untuk kelas ini
    $students = Student::where('classroom_id', $classroomId)
      ->select('id', 'student_number', 'classroom_id')
      ->get();

    Log::info("Loaded students for classroom $classroomId", [
      'count' => $students->count(),
      'course_id' => $courseId,
      'course_name' => $this->course->name
    ]);

    // Buat mapping NIM ke student_id untuk lookup cepat
    foreach ($students as $student) {
      // Gunakan NIM sebagai kunci untuk mapping
      $this->studentMapping[trim($student->student_number)] = [
        'id' => $student->id,
        'classroom_id' => $student->classroom_id,
        'nim' => $student->student_number
      ];
    }

    Log::info('Student mapping created', [
      'total_mappings' => count($this->studentMapping)
    ]);
  }

  public function collection(Collection $rows)
  {
    Log::info('Starting custom dosen excel import with ' . count($rows) . ' rows');

    // Use transaction for data integrity
    DB::beginTransaction();

    try {
      // Persiapkan array untuk mapping header kolom
      $headerMap = [];
      $nimColumn = null;

      // Jika rows ada isinya, gunakan baris pertama untuk identifikasi kolom
      if ($rows->isNotEmpty()) {
        $firstRow = $rows->first();

        // Cari kolom NIM (bisa 'nim', 'NIM', 'Nim', dll)
        foreach ($firstRow as $key => $value) {
          $keyLower = strtolower($key);
          if (str_contains($keyLower, 'nim')) {
            $nimColumn = $key;
            break;
          }
        }

        // Jika kolom NIM tidak ditemukan, coba alternatif nama kolom
        if (!$nimColumn) {
          foreach ($firstRow as $key => $value) {
            if (
              str_contains(strtolower($key), 'nomor') ||
              str_contains(strtolower($key), 'student') ||
              str_contains(strtolower($key), 'mahasiswa')
            ) {
              $nimColumn = $key;
              break;
            }
          }
        }

        // Petakan kolom lain (partisipatif, proyek, uts, uas) berdasarkan nama kolom
        foreach ($firstRow as $key => $value) {
          $keyLower = strtolower($key);

          // Identifikasi kolom partisipatif/kehadiran
          if (
            str_contains($keyLower, 'partisipatif') ||
            str_contains($keyLower, 'hadir') ||
            str_contains($keyLower, 'absen')
          ) {
            $headerMap['nilai_absensi'] = $key;
          }
          // Identifikasi kolom proyek/tugas
          else if (
            str_contains($keyLower, 'proyek') ||
            str_contains($keyLower, 'tugas') ||
            str_contains($keyLower, 'project')
          ) {
            $headerMap['tugas'] = $key;
          }
          // Identifikasi kolom UTS
          else if (
            str_contains($keyLower, 'uts') ||
            str_contains($keyLower, 'tengah')
          ) {
            $headerMap['uts'] = $key;
          }
          // Identifikasi kolom UAS
          else if (
            str_contains($keyLower, 'uas')
          ) {
            $headerMap['uas'] = $key;
          }
        }
      }

      Log::info('Column mapping detected:', [
        'nim_column' => $nimColumn,
        'header_map' => $headerMap
      ]);

      // Validasi bahwa kita bisa menemukan kolom penting
      if (!$nimColumn) {
        throw new \Exception("Tidak dapat menemukan kolom NIM di file Excel. Silakan pastikan ada kolom bernama NIM atau yang serupa.");
      }

      if (empty($headerMap)) {
        throw new \Exception("Tidak dapat mengenali kolom nilai di file Excel. Pastikan ada kolom bernama Partisipatif, Proyek, UTS, UAS atau yang serupa.");
      }

      // Proses setiap baris data
      foreach ($rows as $index => $row) {
        $this->importResults['rows_processed']++;

        // Ambil NIM dari kolom yang sudah diidentifikasi
        $nim = isset($row[$nimColumn]) ? trim((string)$row[$nimColumn]) : null;

        // Skip baris tanpa NIM atau NIM tidak valid
        if (empty($nim) || !is_numeric($nim)) {
          Log::info("Skipping row $index (Empty or invalid NIM): " . json_encode($row));
          continue;
        }

        // Cari student_id berdasarkan NIM
        $studentData = $this->studentMapping[$nim] ?? null;

        if (!$studentData) {
          $this->importResults['students_not_found']++;
          Log::warning("Student not found for NIM: $nim");
          continue;
        }

        $studentId = $studentData['id'];

        // Proses nilai berdasarkan mapping yang ditemukan
        foreach ($headerMap as $kategori => $kolom) {
          // Lewati jika kolom kosong atau nilai tidak ada
          if (!isset($row[$kolom]) || $row[$kolom] === '' || $row[$kolom] === null) {
            continue;
          }

          $nilai = $row[$kolom];

          // Khusus untuk nilai absensi
          if ($kategori === 'nilai_absensi') {
            // Konversi nilai partisipatif ke jumlah pertemuan (asumsi: nilai 100 = 16 pertemuan)
            $totalMeetings = $this->convertPartisipatifToMeetings($nilai);
            $this->processAttendance($studentId, $totalMeetings, $index);
          }
          // Untuk nilai-nilai lain (tugas, uts, uas)
          else {
            $this->processGrade($studentId, $kategori, $nilai, $index);
          }
        }
      }

      DB::commit();
      Log::info('Custom dosen excel import completed successfully', $this->importResults);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('Error in custom dosen excel import: ' . $e->getMessage());
      Log::error($e->getTraceAsString());

      $this->importResults['error']++;
      $this->importResults['exception'] = $e->getMessage();

      throw $e;
    }
  }

  /**
   * Konversi nilai partisipatif (biasanya 0-100) ke jumlah pertemuan (0-16)
   */
  protected function convertPartisipatifToMeetings($partisipatifValue)
  {
    if (!is_numeric($partisipatifValue)) {
      return 0;
    }

    $partisipatifValue = (float)$partisipatifValue;

    // Jika nilai sudah dalam bentuk 0-16, gunakan langsung
    if ($partisipatifValue >= 0 && $partisipatifValue <= 16) {
      return round($partisipatifValue);
    }

    // Jika nilai dalam bentuk 0-100, konversi ke 0-16
    if ($partisipatifValue >= 0 && $partisipatifValue <= 100) {
      return round(($partisipatifValue / 100) * 16);
    }

    // Untuk nilai di luar jangkauan yang diharapkan
    return 0;
  }

  protected function processGrade($studentId, $category, $value, $rowIndex)
  {
    try {
      // Pastikan nilai valid dan dalam rentang
      if (!is_numeric($value)) {
        $this->importResults['grades_skipped']++;
        Log::warning("Non-numeric value", ['category' => $category, 'value' => $value]);
        return false;
      }

      $roundedValue = round((float)$value);

      // Normalisasi nilai ke rentang 0-100 jika perlu
      if ($roundedValue > 100) {
        // Jika nilai di luar jangkauan, coba normalkan (misal: nilai 4.0 skala)
        if ($roundedValue <= 4) {
          $roundedValue = $roundedValue * 25; // Konversi skala 4.0 ke 0-100
        } else {
          $this->importResults['grades_skipped']++;
          Log::warning("Value out of range", ['category' => $category, 'value' => $value]);
          return false;
        }
      }

      // Cek apakah grade sudah ada
      $existingGrade = Grade::where([
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $this->classroomId,
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
            'classroom_id' => $this->classroomId,
            'category' => $category,
            'old_value' => $oldValue,
            'new_value' => $roundedValue
          ]);
        } else {
          $this->importResults['error']++;
          return false;
        }
      } else {
        // Buat grade baru
        $grade = new Grade();
        $grade->student_id = $studentId;
        $grade->course_id = $this->courseId;
        $grade->classroom_id = $this->classroomId;
        $grade->grade = $roundedValue;
        $grade->category = $category;
        $grade->section = null;
        $result = $grade->save();

        if ($result) {
          $this->importResults['grades_success']++;
          Log::info("Created grade", [
            'student_id' => $studentId,
            'course_id' => $this->courseId,
            'classroom_id' => $this->classroomId,
            'category' => $category,
            'value' => $roundedValue
          ]);
        } else {
          $this->importResults['error']++;
          return false;
        }
      }

      return true;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing grade", [
        'student_id' => $studentId,
        'category' => $category,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  protected function processAttendance($studentId, $totalMeetings, $rowIndex)
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
        'classroom_id' => $this->classroomId,
      ])->delete();

      // Buat absensi baru untuk setiap pertemuan yang dihadiri
      $attendanceCreated = 0;

      for ($section = 1; $section <= $totalMeetings; $section++) {
        $attendance = new Attendance();
        $attendance->student_id = $studentId;
        $attendance->course_id = $this->courseId;
        $attendance->classroom_id = $this->classroomId;
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
        'classroom_id' => $this->classroomId,
        'total_meetings' => $totalMeetings,
        'records_created' => $attendanceCreated
      ]);

      return $attendanceCreated > 0;
    } catch (\Exception $e) {
      $this->importResults['error']++;
      Log::error("Error processing attendance", [
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $this->classroomId,
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  public function getImportResults()
  {
    return $this->importResults;
  }
}
