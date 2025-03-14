<?php

namespace App\Imports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AttendanceImport implements ToCollection, WithStartRow, WithHeadingRow
{
  protected $course;
  protected $classroom;

  public function __construct($course, $classroom)
  {
    $this->course = $course;
    $this->classroom = $classroom;
  }

  /**
   * @return int
   */
  public function startRow(): int
  {
    return 9; // Start from row 9 (the header row)
  }

  /**
   * @return int
   */
  public function headingRow(): int
  {
    return 9; // Headings are on row 9
  }

  /**
   * @param Collection $rows
   */
  public function collection(Collection $rows)
  {
    $attendances = [];
    $now = Carbon::now();

    foreach ($rows as $row) {
      $studentId = $row['id']; // This is the hidden student ID column

      // Process each attendance section (pertemuan)
      for ($i = 1; $i <= 16; $i++) {
        $colName = "pertemuan_$i"; // Match the heading names

        // Convert row value to an appropriate format
        $value = trim($row[$colName]);

        // Only process non-empty cells with value '1' (present)
        if ($value === '1' || $value === 1) {
          // Check if attendance record already exists to avoid duplicates
          $exists = Attendance::where([
            'student_id' => $studentId,
            'course_id' => $this->course->id,
            'classroom_id' => $this->classroom->id,
            'section' => $i,
          ])->exists();

          if (!$exists) {
            $attendances[] = [
              'student_id' => $studentId,
              'course_id' => $this->course->id,
              'classroom_id' => $this->classroom->id,
              'section' => $i,
              'status' => true,
              'created_at' => $now,
              'updated_at' => $now,
            ];
          }
        }
      }
    }

    // Insert all attendance records in one query for efficiency
    if (!empty($attendances)) {
      Attendance::insert($attendances);
    }
  }
}
