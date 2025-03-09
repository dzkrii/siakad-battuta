<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\Course;
use App\Models\Classroom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class MultiCourseGradeTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
  protected $classroomId;
  protected $courses;

  public function __construct($classroomId)
  {
    $this->classroomId = $classroomId;

    // Get all courses for this classroom
    $this->courses = Course::whereHas('classrooms', function ($query) use ($classroomId) {
      $query->where('classrooms.id', $classroomId);
    })
      ->select('id', 'name', 'course_code')
      ->get();
  }

  public function collection()
  {
    $students = Student::where('classroom_id', $this->classroomId)
      ->select('id', 'student_number as nim', 'user_id')
      ->with('user:id,name')
      ->get();

    $rows = new Collection();

    // For each student, create a row for each course
    foreach ($students as $student) {
      foreach ($this->courses as $course) {
        $rows->push([
          'nim' => $student->nim,
          'name' => $student->user->name,
          'kode_mk' => $course->course_code,
          'nama_mk' => $course->name,
          'nilai_tugas' => '',
          'nilai_uts' => '',
          'nilai_uas' => ''
        ]);
      }
    }

    return $rows;
  }

  public function headings(): array
  {
    return [
      'nim',
      'name',
      'kode_mk',
      'nama_mk',
      'nilai_tugas',
      'nilai_uts',
      'nilai_uas'
    ];
  }

  public function title(): string
  {
    $classroom = Classroom::find($this->classroomId);
    return 'Template Nilai Multi MK - ' . ($classroom->name ?? 'Kelas');
  }

  public function styles(Worksheet $sheet)
  {
    // Style for the header row
    $sheet->getStyle('A1:G1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E0E0E0']
      ]
    ]);

    // Auto-size columns
    foreach (range('A', 'G') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Freeze the header row
    $sheet->freezePane('A2');

    // Add instructions at the bottom
    $lastRow = $sheet->getHighestRow() + 2;
    $sheet->setCellValue('A' . $lastRow, 'PETUNJUK PENGISIAN:');
    $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '1. Jangan mengubah kolom nim, name, kode_mk, dan nama_mk');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '2. Isi nilai dalam rentang 0-100');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '3. Kolom yang kosong akan diabaikan');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '4. Anda dapat mengisi salah satu atau semua kolom nilai');

    // Merge cells for instruction text
    foreach (range($lastRow - 4, $lastRow) as $row) {
      $sheet->mergeCells('A' . $row . ':G' . $row);
    }

    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
