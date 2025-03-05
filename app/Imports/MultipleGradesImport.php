<?php

namespace App\Imports;

use App\Models\Grade;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Collection;

class MultipleGradesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
  use Importable;

  protected $classroomId;
  protected $courseId;

  public function __construct($classroomId, $courseId)
  {
    $this->classroomId = $classroomId;
    $this->courseId = $courseId;
  }

  public function collection(Collection $rows)
  {
    $successCount = 0;

    foreach ($rows as $row) {
      Log::info('Processing row data:', ['student_id' => $row['student_id'] ?? 'unknown', 'row' => $row]);

      if (!isset($row['student_id'])) {
        Log::error('Baris tidak memiliki student_id, dilewati');
        continue;
      }

      // Proses nilai tugas
      if (isset($row['nilai_tugas']) && $row['nilai_tugas'] !== null && $row['nilai_tugas'] !== '') {
        $saved = $this->processGrade($row['student_id'], 'tugas', $row['nilai_tugas']);
        if ($saved) $successCount++;
      }

      // Proses nilai UTS
      if (isset($row['nilai_uts']) && $row['nilai_uts'] !== null && $row['nilai_uts'] !== '') {
        $saved = $this->processGrade($row['student_id'], 'uts', $row['nilai_uts']);
        if ($saved) $successCount++;
      }

      // Proses nilai UAS
      if (isset($row['nilai_uas']) && $row['nilai_uas'] !== null && $row['nilai_uas'] !== '') {
        $saved = $this->processGrade($row['student_id'], 'uas', $row['nilai_uas']);
        if ($saved) $successCount++;
      }
    }

    Log::info("Import selesai dengan $successCount nilai berhasil disimpan");
  }

  private function processGrade($studentId, $category, $nilaiValue)
  {
    try {
      // Perhatikan: kita perlu pastikan nilai adalah numerik
      if (!is_numeric($nilaiValue)) {
        Log::warning("Nilai $category untuk mahasiswa ID $studentId tidak numerik: '$nilaiValue'");
        return false;
      }

      // Cek apakah sudah ada nilai untuk siswa ini
      $existingGrade = Grade::where([
        'student_id' => $studentId,
        'course_id' => $this->courseId,
        'classroom_id' => $this->classroomId,
        'category' => $category,
      ])->whereNull('section')->first();

      if ($existingGrade) {
        Log::info("Nilai $category sudah ada untuk mahasiswa ID $studentId: {$existingGrade->grade}. Skipping.");
        return false;
      }

      // Buat record nilai baru
      $grade = new Grade();
      $grade->student_id = $studentId;
      $grade->course_id = $this->courseId;
      $grade->classroom_id = $this->classroomId;
      $grade->grade = round((float)$nilaiValue);
      $grade->category = $category;
      $grade->section = null;

      $result = $grade->save();

      Log::info("Menyimpan nilai $category untuk mahasiswa ID $studentId: " . round((float)$nilaiValue) . " - Result: " . ($result ? 'Success' : 'Failed'));

      return $result;
    } catch (\Exception $e) {
      Log::error("Error saat menyimpan nilai $category untuk mahasiswa ID $studentId: " . $e->getMessage());
      Log::error($e->getTraceAsString());
      return false;
    }
  }

  public function rules(): array
  {
    return [
      'student_id' => 'required|exists:students,id',
      'nilai_tugas' => 'nullable|numeric|min:0|max:100',
      'nilai_uts' => 'nullable|numeric|min:0|max:100',
      'nilai_uas' => 'nullable|numeric|min:0|max:100',
    ];
  }
}
