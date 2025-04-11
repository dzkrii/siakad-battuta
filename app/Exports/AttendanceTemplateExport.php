<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell
{
  protected $course;
  protected $classroom;
  protected $students;

  public function __construct($course, $classroom, $students)
  {
    $this->course = $course;
    $this->classroom = $classroom;
    $this->students = $students;
  }

  public function collection()
  {
    return $this->students;
  }

  public function headings(): array
  {
    $headings = [
      'ID',
      'No',
      'NIM',
      'Nama Mahasiswa',
    ];

    // Add 16 columns for attendance sections (pertemuan)
    for ($i = 1; $i <= 16; $i++) {
      $headings[] = "$i";
    }

    return $headings;
  }

  public function map($student): array
  {
    $row = [
      $student->id, // Student ID (hidden)
      $this->students->search($student) + 1, // Row number (1-based index)
      $student->student_number, // NIM
      $student->user->name, // Student Name
    ];

    // Add 16 empty cells for attendance marks
    for ($i = 1; $i <= 16; $i++) {
      $row[] = ""; // Empty cell for attendance
    }

    return $row;
  }

  public function styles(Worksheet $sheet)
  {
    // Add title and information at the top
    $sheet->mergeCells('B1:T1');
    $sheet->setCellValue('B1', 'DAFTAR HADIR MAHASISWA');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

    // Add course and classroom information
    $sheet->setCellValue('C2', 'Program Studi');
    $sheet->setCellValue('D2', ': ' . $this->classroom->department->name);
    // $sheet->mergeCells('C2:D2');

    $sheet->setCellValue('C3', 'Kelas');
    $sheet->setCellValue('D3', ': ' . $this->classroom->name);
    // $sheet->mergeCells('C3:D3');

    $sheet->setCellValue('C4', 'Mata Kuliah');
    $sheet->setCellValue('D4', ': ' . $this->course->name . ' (' . $this->course->kode_matkul . ')');
    // $sheet->mergeCells('C4:D4');

    $sheet->setCellValue('C5', 'Dosen Pengampu');
    // Assuming the teacher's name can be fetched, otherwise hardcode or remove
    $sheet->setCellValue('D5', ': ' . ($this->course->teacher->user->name ?? 'Dosen Pengampu'));
    // $sheet->mergeCells('C5:D5');

    // Add schedule information
    $schedule = \App\Models\Schedule::where('course_id', $this->course->id)
      ->where('classroom_id', $this->classroom->id)
      ->first();

    if ($schedule) {
      $sheet->setCellValue('C6', 'Jadwal');
      $sheet->setCellValue('D6', ': ' . $schedule->day_of_week->value . ' / ' . $schedule->start_time . ' - ' . $schedule->end_time);
    }

    $sheet->setCellValue('C7', 'Tahun Akademik');
    $sheet->setCellValue('D7', ': ' . (activeAcademicYear()->name) . ' (' . (activeAcademicYear()->semester->value) . ')');
    // $sheet->mergeCells('C6:D6');

    // Instructions row
    // $sheet->mergeCells('B7:T7');
    $sheet->setCellValue('B8', '');
    $sheet->getStyle('B8')->getFont()->setBold(true);
    $sheet->getStyle('B8')->getFont()->getColor()->setARGB('FF0000FF'); // Blue text

    // Add NB note
    $sheet->mergeCells('B9:T9');
    $sheet->setCellValue('B9', 'NB: Bagi mahasiswa yang tidak ada namanya di daftar hadir ini namun mahasiswa tersebut masuk kedalam kelas, mohon konfirmasi ke akademik terlebih dahulu. Jangan menambahkan nama mahasiswa secara manual');
    $sheet->getStyle('B9')->getFont()->setBold(true);
    $sheet->getStyle('B9')->getFont()->getColor()->setARGB('FFFF0000'); // Red text
    $sheet->getStyle('B9')->getAlignment()->setHorizontal('left');

    // Add "Pertemuan" merged cell above the attendance columns
    $sheet->mergeCells('E10:T10');
    $sheet->setCellValue('E10', 'Pertemuan');
    $sheet->getStyle('E10')->getFont()->setBold(true);
    $sheet->getStyle('E10')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('E10')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "No" merged cell
    $sheet->mergeCells('B10:B11');
    $sheet->setCellValue('B10', 'No');
    $sheet->getStyle('B10')->getFont()->setBold(true);
    $sheet->getStyle('B10')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('B10')->getAlignment()->setVertical('center');
    $sheet->getStyle('B10')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "NIM" merged cell
    $sheet->mergeCells('C10:C11');
    $sheet->setCellValue('C10', 'NIM');
    $sheet->getStyle('C10')->getFont()->setBold(true);
    $sheet->getStyle('C10')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('C10')->getAlignment()->setVertical('center');
    $sheet->getStyle('C10')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "Nama Mahasiswa" merged cell
    $sheet->mergeCells('D10:D11');
    $sheet->setCellValue('D10', 'Nama Mahasiswa');
    $sheet->getStyle('D10')->getFont()->setBold(true);
    $sheet->getStyle('D10')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('D10')->getAlignment()->setVertical('center');
    $sheet->getStyle('D10')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Style for headers in row 11 (now only for attendance columns)
    $headerRange = 'E11:T11';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($headerRange)->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Border for all data and headers
    $dataRange = 'A11:T' . (11 + $this->students->count());
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Add border for the merged cells in row 10
    $sheet->getStyle('B10:T10')->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Set row height for student data rows
    for ($i = 12; $i <= 11 + $this->students->count(); $i++) {
      $sheet->getRowDimension($i)->setRowHeight(25);
    }

    // Center alignment for No Column
    $noRange = 'B12:B' . (11 + $this->students->count());
    $sheet->getStyle($noRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($noRange)->getAlignment()->setVertical('center');

    // Center alignment for NIM Column (both horizontal and vertical)
    $nimRange = 'C12:C' . (11 + $this->students->count());
    $sheet->getStyle($nimRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($nimRange)->getAlignment()->setVertical('center');

    // Left alignment for Nama Mahasiswa Column (horizontal) and center (vertical)
    $namaRange = 'D12:D' . (11 + $this->students->count());
    $sheet->getStyle($namaRange)->getAlignment()->setHorizontal('left');
    $sheet->getStyle($namaRange)->getAlignment()->setVertical('center');

    // Center alignment for all attendance columns
    $attendanceRange = 'E12:T' . (11 + $this->students->count());
    $sheet->getStyle($attendanceRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($attendanceRange)->getAlignment()->setVertical('center');

    // Hide only the ID column (A) after the data rows
    $sheet->getColumnDimension('A')->setVisible(false);

    // Auto-size columns for information section
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);

    return [
      // Set all cells to have borders
      10 => ['font' => ['bold' => true]],
    ];
  }

  public function columnWidths(): array
  {
    $widths = [
      'A' => 8,  // ID column (hidden)
      'B' => 8,  // No column
      'C' => 15, // NIM 
      'D' => 40, // Student Name
    ];

    // Set width for attendance columns
    for ($i = 0; $i < 16; $i++) {
      $column = chr(69 + $i); // E to T (ASCII for E is 69)
      $widths[$column] = 12;
    }

    return $widths;
  }

  public function startCell(): string
  {
    return 'A11';
  }
}
