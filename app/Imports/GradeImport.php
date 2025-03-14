<?php

namespace App\Imports;

use App\Models\Grade;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class GradeImport implements ToCollection, WithStartRow, WithHeadingRow
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
    $now = Carbon::now();
    $grades = [];

    foreach ($rows as $row) {
      $studentId = $row['id']; // Hidden student ID column

      $categories = [
        'tugas' => trim($row['tugas'] ?? ''),
        'uts' => trim($row['uts'] ?? ''),
        'uas' => trim($row['uas'] ?? '')
      ];

      foreach ($categories as $category => $value) {
        // Skip empty or non-numeric values
        if (empty($value) || !is_numeric($value)) {
          continue;
        }

        // Convert to integer and validate range
        $gradeValue = (int) $value;
        if ($gradeValue < 0 || $gradeValue > 100) {
          continue;
        }

        // Check if grade already exists
        $existingGrade = Grade::where([
          'student_id' => $studentId,
          'course_id' => $this->course->id,
          'classroom_id' => $this->classroom->id,
          'category' => $category,
          'section' => null,
        ])->first();

        if ($existingGrade) {
          // Update existing grade
          $existingGrade->update([
            'grade' => $gradeValue,
            'updated_at' => $now,
          ]);
        } else {
          // Create new grade
          Grade::create([
            'student_id' => $studentId,
            'course_id' => $this->course->id,
            'classroom_id' => $this->classroom->id,
            'category' => $category,
            'section' => null,
            'grade' => $gradeValue,
            'created_at' => $now,
            'updated_at' => $now,
          ]);
        }
      }
    }
  }
}
