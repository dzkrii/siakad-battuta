<?php

namespace App\Exports;

use App\Models\Student;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Classroom;
use App\Enums\ScheduleDay;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class CourseSchedulesTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
  protected $courseId;
  protected $classroomIds = [];
  protected $course;
  protected $schedules;

  public function __construct($courseId)
  {
    $this->courseId = $courseId;
    $this->course = Course::find($courseId);

    // Dapatkan semua jadwal (schedules) untuk mata kuliah ini
    $this->schedules = Schedule::where('course_id', $courseId)
      ->select('id', 'classroom_id', 'day_of_week', 'start_time', 'end_time')
      ->get();

    // Kumpulkan semua classroom_id dari jadwal
    $this->classroomIds = $this->schedules->pluck('classroom_id')->unique()->toArray();
  }

  public function collection()
  {
    $rows = new Collection();

    // Untuk setiap kelas
    foreach ($this->classroomIds as $classroomId) {
      $classroom = Classroom::find($classroomId);
      $jadwalInfo = $this->getJadwalInfo($classroomId);

      // Dapatkan semua mahasiswa di kelas ini
      $students = Student::where('classroom_id', $classroomId)
        ->select('id', 'student_number as nim', 'user_id')
        ->with('user:id,name')
        ->get();

      // Tambahkan baris untuk setiap mahasiswa
      foreach ($students as $student) {
        $rows->push([
          'nim' => $student->nim,
          'name' => $student->user->name,
          'classroom_id' => $classroomId,
          'classroom_name' => $classroom->name ?? "Kelas ID: $classroomId",
          'jadwal' => $jadwalInfo,
          'nilai_tugas' => '',
          'nilai_uts' => '',
          'nilai_uas' => ''
        ]);
      }

      // Tambahkan separator antar kelas
      if ($classroomId !== end($this->classroomIds)) {
        $rows->push([
          'nim' => '',
          'name' => '----------------------',
          'classroom_id' => '',
          'classroom_name' => '----------------------',
          'jadwal' => '----------------------',
          'nilai_tugas' => '',
          'nilai_uts' => '',
          'nilai_uas' => ''
        ]);
      }
    }

    return $rows;
  }

  protected function getJadwalInfo($classroomId)
  {
    $jadwal = $this->schedules->where('classroom_id', $classroomId);
    if ($jadwal->isEmpty()) {
      return 'Tidak ada jadwal';
    }

    $info = [];
    foreach ($jadwal as $schedule) {
      $day = $this->dayName($schedule->day_of_week);
      $startTime = $schedule->start_time;
      $endTime = $schedule->end_time;
      $info[] = "{$day} {$startTime}-{$endTime}";
    }

    return implode(', ', $info);
  }

  protected function dayName($dayOfWeek)
  {
    // Handle jika dayOfWeek adalah Enum
    if ($dayOfWeek instanceof ScheduleDay) {
      $dayValue = $dayOfWeek->value;
    } else {
      // Handle jika dayOfWeek adalah integer
      $dayValue = $dayOfWeek;
    }

    $days = [
      1 => 'Senin',
      2 => 'Selasa',
      3 => 'Rabu',
      4 => 'Kamis',
      5 => 'Jumat',
      6 => 'Sabtu',
      7 => 'Minggu',
    ];

    // Jika dayValue adalah 'MONDAY', 'TUESDAY', dll.
    if (is_string($dayValue)) {
      $dayMapping = [
        'MONDAY' => 'Senin',
        'TUESDAY' => 'Selasa',
        'WEDNESDAY' => 'Rabu',
        'THURSDAY' => 'Kamis',
        'FRIDAY' => 'Jumat',
        'SATURDAY' => 'Sabtu',
        'SUNDAY' => 'Minggu',
      ];

      return $dayMapping[$dayValue] ?? "Hari-{$dayValue}";
    }

    return $days[$dayValue] ?? "Hari-{$dayValue}";
  }

  public function headings(): array
  {
    return [
      'nim',
      'name',
      'classroom_id',
      'classroom_name',
      'jadwal',
      'nilai_tugas',
      'nilai_uts',
      'nilai_uas'
    ];
  }

  public function title(): string
  {
    return 'Template Nilai ' . ($this->course->name ?? 'Mata Kuliah');
  }

  public function styles(Worksheet $sheet)
  {
    // Style for the header row
    $sheet->getStyle('A1:H1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E0E0E0']
      ]
    ]);

    // Auto-size columns
    foreach (range('A', 'H') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Freeze the header row
    $sheet->freezePane('A2');

    // Add instructions at the bottom
    $lastRow = $sheet->getHighestRow() + 2;
    $sheet->setCellValue('A' . $lastRow, 'PETUNJUK PENGISIAN:');
    $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '1. Jangan mengubah kolom nim, name, classroom_id, classroom_name, dan jadwal');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '2. Isi nilai dalam rentang 0-100');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '3. Kolom yang kosong akan diabaikan');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '4. Anda dapat mengisi salah satu atau semua kolom nilai');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '5. Data dikelompokkan per kelas dengan jadwal yang berbeda');

    // Merge cells for instruction text
    foreach (range($lastRow - 5, $lastRow) as $row) {
      $sheet->mergeCells('A' . $row . ':H' . $row);
    }

    // Set nilai_tugas, nilai_uts, nilai_uas columns to have a light yellow background
    $lastDataRow = $sheet->getHighestRow() - 6; // Excluding instruction rows
    $sheet->getStyle('F2:H' . $lastDataRow)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFDE7']
      ]
    ]);

    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
