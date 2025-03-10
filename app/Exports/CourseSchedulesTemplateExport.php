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

    // Dapatkan semua jadwal untuk mata kuliah ini
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
          'course_id' => $this->courseId, // Menambahkan course_id untuk validasi
          'nama_mata_kuliah' => $this->course->name ?? '',
          'jadwal' => $jadwalInfo,
          'nilai_absensi' => '',
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
          'course_id' => '',
          'nama_mata_kuliah' => '----------------------',
          'jadwal' => '----------------------',
          'nilai_absensi' => '',
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
      'NIM',
      'Nama',
      'Classroom ID',
      'Kelas',
      'Course ID', // Tambahkan kolom baru untuk Course ID
      'Nama Mata Kuliah',
      'Jadwal',
      'Nilai Absensi',
      'Nilai Tugas',
      'Nilai UTS',
      'Nilai UAS'
    ];
  }

  public function title(): string
  {
    return 'Template Nilai ' . ($this->course->name ?? 'Mata Kuliah');
  }

  public function styles(Worksheet $sheet)
  {
    // Style for the header row
    $sheet->getStyle('A1:K1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E0E0E0']
      ]
    ]);

    // Auto-size columns
    foreach (range('A', 'K') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Freeze the header row
    $sheet->freezePane('A2');

    // Add instructions at the bottom
    $lastRow = $sheet->getHighestRow() + 2;
    $sheet->setCellValue('A' . $lastRow, 'PETUNJUK PENGISIAN:');
    $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '1. Jangan mengubah kolom nim, name, classroom_id, classroom_name, course_id, nama_mata_kuliah, dan jadwal');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '2. Isi nilai dalam rentang 0-100');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '3. Nilai Absensi diisi dengan angka 1-16 (jumlah pertemuan yang dihadiri)');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '4. Kolom yang kosong akan diabaikan');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '5. Anda dapat mengisi salah satu atau semua kolom nilai');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '6. Data dikelompokkan per kelas dengan jadwal yang berbeda');

    // Merge cells for instruction text
    foreach (range($lastRow - 6, $lastRow) as $row) {
      $sheet->mergeCells('A' . $row . ':K' . $row);
    }

    // Set course_id dan nama mata kuliah dengan warna latar berbeda
    $lastDataRow = $sheet->getHighestRow() - 7; // Excluding instruction rows
    $sheet->getStyle('E2:F' . $lastDataRow)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFF9C4'] // Light yellow background for course info
      ]
    ]);

    // Set nilai_absensi dengan warna latar berbeda 
    $sheet->getStyle('H2:H' . $lastDataRow)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E8F5E9'] // Light green background for absensi
      ]
    ]);

    // Set nilai_tugas, nilai_uts, nilai_uas columns to have a light blue background
    $sheet->getStyle('I2:K' . $lastDataRow)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E3F2FD'] // Light blue for nilai
      ]
    ]);

    // Sembunyikan (hide) kolom course_id karena hanya untuk validasi
    // Tapi tetap bisa diakses oleh sistem saat import
    $sheet->getColumnDimension('E')->setVisible(false);

    return [
      1 => ['font' => ['bold' => true]],
    ];
  }
}
