<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradeTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
  protected $classroomId;
  protected $courseId;

  public function __construct($classroomId, $courseId)
  {
    $this->classroomId = $classroomId;
    $this->courseId = $courseId;
  }

  public function collection()
  {
    $students = Student::where('classroom_id', $this->classroomId)
      ->select('id', 'student_number as nim', 'user_id')
      ->with('user:id,name')
      ->get();

    return $students->map(function ($student) {
      return [
        'student_id' => $student->id,
        'nim' => $student->nim,
        'name' => $student->user->name,
        'nilai_tugas' => '',
        'nilai_uts' => '',
        'nilai_uas' => ''
      ];
    });
  }

  public function headings(): array
  {
    return [
      'student_id',
      'nim',
      'name',
      'nilai_tugas',
      'nilai_uts',
      'nilai_uas'
    ];
  }

  public function title(): string
  {
    return 'Template Nilai';
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
