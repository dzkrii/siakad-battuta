<?php

namespace App\Imports;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class AttendancesImport implements ToCollection, WithHeadingRow, WithValidation
{
  protected $classroomId;
  protected $courseId;
  protected $totalMeetings = 16;

  public function __construct($classroomId, $courseId)
  {
    $this->classroomId = $classroomId;
    $this->courseId = $courseId;
  }

  public function collection(Collection $rows)
  {
    foreach ($rows as $row) {
      for ($i = 1; $i <= $this->totalMeetings; $i++) {
        $key = 'pertemuan_' . $i;

        if (!isset($row[$key])) {
          continue;
        }

        $status = $row[$key];
        $student_id = $row['student_id'];

        // Jika status hadir (1), masukkan ke database
        if ($status == 1) {
          // Cek apakah sudah ada data absensi
          $existingAttendance = Attendance::where([
            'student_id' => $student_id,
            'course_id' => $this->courseId,
            'classroom_id' => $this->classroomId,
            'section' => $i
          ])->first();

          if (!$existingAttendance) {
            Attendance::create([
              'student_id' => $student_id,
              'course_id' => $this->courseId,
              'classroom_id' => $this->classroomId,
              'status' => true,
              'section' => $i
            ]);
            Log::info("Created attendance for student ID {$student_id}, pertemuan {$i}");
          }
        }
      }
    }
  }

  public function rules(): array
  {
    $rules = [
      'student_id' => 'required|exists:students,id',
    ];

    for ($i = 1; $i <= $this->totalMeetings; $i++) {
      $rules['pertemuan_' . $i] = 'nullable|in:0,1';
    }

    return $rules;
  }
}
