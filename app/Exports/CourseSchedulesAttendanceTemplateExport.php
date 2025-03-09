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

class CourseSchedulesAttendanceTemplateExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
  protected $courseId;
  protected $classroomIds = [];
  protected $course;
  protected $schedules;
  protected $totalMeetings = 16;

  public function __construct($courseId, $totalMeetings = 16)
  {
    $this->courseId = $courseId;
    $this->course = Course::find($courseId);
    $this->totalMeetings = $totalMeetings;

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
        $row = [
          'nim' => $student->nim,
          'name' => $student->user->name,
          'classroom_id' => $classroomId,
          'classroom_name' => $classroom->name ?? "Kelas ID: $classroomId",
          'jadwal' => $jadwalInfo,
        ];

        // Tambahkan kolom untuk setiap pertemuan
        for ($i = 1; $i <= $this->totalMeetings; $i++) {
          $row['pertemuan_' . $i] = '';
        }

        $rows->push($row);
      }

      // Tambahkan separator antar kelas
      if ($classroomId !== end($this->classroomIds)) {
        $separatorRow = [
          'nim' => '',
          'name' => '----------------------',
          'classroom_id' => '',
          'classroom_name' => '----------------------',
          'jadwal' => '----------------------',
        ];

        // Tambahkan kolom kosong untuk setiap pertemuan di separator
        for ($i = 1; $i <= $this->totalMeetings; $i++) {
          $separatorRow['pertemuan_' . $i] = '';
        }

        $rows->push($separatorRow);
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
    $headings = [
      'NIM',
      'Nama',
      'Classroom ID',
      'Kelas',
      'Jadwal',
    ];

    // Tambahkan kolom pertemuan
    for ($i = 1; $i <= $this->totalMeetings; $i++) {
      $headings[] = 'Pertemuan ' . $i;
    }

    return $headings;
  }

  public function title(): string
  {
    return 'Absensi ' . ($this->course->name ?? 'Mata Kuliah');
  }

  public function styles(Worksheet $sheet)
  {
    // Style for the header row
    $headerLetter = $this->getHeaderLetter($this->totalMeetings);
    $sheet->getStyle('A1:' . $headerLetter . '1')->applyFromArray([
      'font' => ['bold' => true],
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E0E0E0']
      ]
    ]);

    // Auto-size columns for NIM, Name, Classroom ID, Kelas, Jadwal
    foreach (range('A', 'E') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Set width for pertemuan columns
    $startColIndex = 5; // 'F' is the 6th column (0-indexed)
    for ($i = 0; $i < $this->totalMeetings; $i++) {
      $colLetter = $this->getColumnLetter($startColIndex + $i);
      $sheet->getColumnDimension($colLetter)->setWidth(12);
    }

    // Freeze the first 5 columns and header row
    $sheet->freezePane('F2');

    // Add instructions at the bottom
    $lastRow = $sheet->getHighestRow() + 2;
    $sheet->setCellValue('A' . $lastRow, 'PETUNJUK PENGISIAN:');
    $sheet->getStyle('A' . $lastRow)->getFont()->setBold(true);

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '1. Jangan mengubah kolom NIM, Nama, Classroom ID, Kelas, dan Jadwal');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '2. Isi dengan angka 1 untuk kehadiran (Hadir)');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '3. Biarkan kosong atau isi 0 untuk ketidakhadiran');

    $lastRow++;
    $sheet->setCellValue('A' . $lastRow, '4. Data dikelompokkan per kelas dengan jadwal yang berbeda');

    // Merge cells for instruction text
    $headerLetter = $this->getHeaderLetter($this->totalMeetings);
    foreach (range($lastRow - 4, $lastRow) as $row) {
      $sheet->mergeCells('A' . $row . ':' . $headerLetter . $row);
    }

    // Set pertemuan columns to have a light yellow background
    $lastDataRow = $sheet->getHighestRow() - 5; // Excluding instruction rows
    $firstPertemuanCol = 'F';
    $lastPertemuanCol = $this->getColumnLetter($startColIndex + $this->totalMeetings - 1);
    $sheet->getStyle($firstPertemuanCol . '2:' . $lastPertemuanCol . $lastDataRow)->applyFromArray([
      'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'FFFDE7']
      ]
    ]);

    return [
      1 => ['font' => ['bold' => true]],
    ];
  }

  // Helper untuk mendapatkan huruf kolom dari indeks (0-based)
  private function getColumnLetter($index)
  {
    $letter = '';
    while ($index >= 0) {
      $letter = chr(65 + ($index % 26)) . $letter;
      $index = floor($index / 26) - 1;
    }
    return $letter;
  }

  // Helper untuk mendapatkan huruf kolom terakhir berdasarkan total pertemuan
  private function getHeaderLetter($totalMeetings)
  {
    // 5 kolom awal (A-E) + totalMeetings
    return $this->getColumnLetter(4 + $totalMeetings);
  }
}
