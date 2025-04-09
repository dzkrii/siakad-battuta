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
    $sheet->mergeCells('B1:S1');
    $sheet->setCellValue('B1', 'ABSENSI MAHASISWA');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('B1')->getAlignment()->setHorizontal('center');

    // Add course and classroom information
    $sheet->setCellValue('B2', 'Program Studi');
    $sheet->setCellValue('C2', ': ' . $this->classroom->department->name);

    $sheet->setCellValue('B3', 'Kelas');
    $sheet->setCellValue('C3', ': ' . $this->classroom->name);

    $sheet->setCellValue('B4', 'Mata Kuliah');
    $sheet->setCellValue('C4', ': ' . $this->course->name . ' (' . $this->course->kode_matkul . ')');

    $sheet->setCellValue('B5', 'Dosen Pengampu');
    // Assuming the teacher's name can be fetched, otherwise hardcode or remove
    $sheet->setCellValue('C5', ': ' . ($this->course->teacher->user->name ?? 'Dosen Pengampu'));

    $sheet->setCellValue('B6', 'Tahun Akademik');
    $sheet->setCellValue('C6', ': ' . (activeAcademicYear()->name) . '(' . (activeAcademicYear()->semester->value) . ')');

    // Instructions row
    $sheet->mergeCells('B7:S7');
    $sheet->setCellValue('B7', 'Petunjuk: Isi kolom "Pertemuan" dengan angka 1 untuk HADIR atau biarkan kosong untuk TIDAK HADIR');
    $sheet->getStyle('B7')->getFont()->setBold(true);
    $sheet->getStyle('B7')->getFont()->getColor()->setARGB('FF0000FF'); // Blue text

    // Style for headers in row 9
    $headerRange = 'A9:S9';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($headerRange)->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Border for all data and headers
    $dataRange = 'A9:S' . (9 + $this->students->count());
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Center alignment for NIM Column
    $nimRange = 'B9:B' . (9 + $this->students->count());
    $sheet->getStyle($nimRange)->getAlignment()->setHorizontal('center');

    // Center alignment for all attendance columns
    $attendanceRange = 'D9:S' . (9 + $this->students->count());
    $sheet->getStyle($attendanceRange)->getAlignment()->setHorizontal('center');

    // Hide only the ID column (A) after the data rows
    $sheet->getColumnDimension('A')->setVisible(false);

    return [
      // Set all cells to have borders
      9 => ['font' => ['bold' => true]],
    ];
  }

  public function columnWidths(): array
  {
    $widths = [
      'A' => 8,  // ID column (hidden)
      'B' => 15, // NIM 
      'C' => 40, // Student Name
    ];

    // Set width for attendance columns
    for ($i = 0; $i < 16; $i++) {
      $column = chr(68 + $i); // D to S (ASCII for D is 68)
      $widths[$column] = 12;
    }

    return $widths;
  }

  public function startCell(): string
  {
    return 'A9';
  }
}
