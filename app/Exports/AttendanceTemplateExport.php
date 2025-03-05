<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
  protected $classroomId;
  protected $courseId;
  protected $totalMeetings = 16;

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
      $row = [
        'student_id' => $student->id,
        'nim' => $student->nim,
        'name' => $student->user->name,
      ];

      // Tambahkan kolom untuk setiap pertemuan
      for ($i = 1; $i <= $this->totalMeetings; $i++) {
        $row['pertemuan_' . $i] = '';
      }

      return $row;
    });
  }

  public function headings(): array
  {
    $headings = [
      'Student ID',
      'NIM',
      'Nama',
    ];

    // Tambahkan kolom pertemuan
    for ($i = 1; $i <= $this->totalMeetings; $i++) {
      $headings[] = 'Pertemuan ' . $i;
    }

    return $headings;
  }

  public function title(): string
  {
    return 'Template Absensi';
  }

  public function styles(Worksheet $sheet)
  {
    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
