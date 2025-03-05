<?php

namespace App\Imports;

use App\Models\Grade;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;

class GradesImport implements ToModel, WithHeadingRow, WithValidation
{
  protected $classroomId;
  protected $courseId;
  protected $category;

  public function __construct($classroomId, $courseId, $category)
  {
    $this->classroomId = $classroomId;
    $this->courseId = $courseId;
    $this->category = $category;
  }

  public function model(array $row)
  {
    // Cek apakah sudah ada nilai untuk mahasiswa ini
    $existingGrade = Grade::where([
      'student_id' => $row['student_id'],
      'course_id' => $this->courseId,
      'classroom_id' => $this->classroomId,
      'category' => $this->category,
    ])->whereNull('section')->first();

    if ($existingGrade) {
      Log::info("Nilai sudah ada untuk mahasiswa ID {$row['student_id']}, kategori {$this->category}. Skipping.");
      return null;
    }

    // Pastikan nilai ada dan valid
    if (!isset($row['nilai']) || empty($row['nilai'])) {
      Log::warning("Nilai kosong untuk mahasiswa ID {$row['student_id']}, kategori {$this->category}. Skipping.");
      return null;
    }

    // Buat nilai baru
    return new Grade([
      'student_id' => $row['student_id'],
      'course_id' => $this->courseId,
      'classroom_id' => $this->classroomId,
      'grade' => round($row['nilai']),
      'category' => $this->category,
      'section' => null
    ]);
  }

  public function rules(): array
  {
    return [
      'student_id' => 'required|exists:students,id',
      'nilai' => 'nullable|numeric|min:0|max:100',
    ];
  }
}
