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

class GradeTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithCustomStartCell
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
    return [
      'ID',
      'NIM',
      'Nama Mahasiswa',
      'Tugas',
      'UTS',
      'UAS',
    ];
  }

  public function map($student): array
  {
    return [
      $student->id, // Student ID (hidden)
      $student->student_number, // NIM
      $student->user->name, // Student Name
      '', // Tugas
      '', // UTS
      '', // UAS
    ];
  }

  public function styles(Worksheet $sheet)
  {
    // Add title and information at the top
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', 'TEMPLATE NILAI MAHASISWA');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

    // Add course and classroom information
    $sheet->setCellValue('A2', 'Program Studi');
    $sheet->setCellValue('B2', ': ' . $this->classroom->department->name);

    $sheet->setCellValue('A3', 'Kelas');
    $sheet->setCellValue('B3', ': ' . $this->classroom->name);

    $sheet->setCellValue('A4', 'Mata Kuliah');
    $sheet->setCellValue('B4', ': ' . $this->course->name . ' (' . $this->course->kode_matkul . ')');

    $sheet->setCellValue('A5', 'Dosen Pengampu');
    $sheet->setCellValue('B5', ': ' . ($this->course->teacher->user->name ?? 'Dosen Pengampu'));

    $sheet->setCellValue('A6', 'Tahun Akademik');
    $sheet->setCellValue('B6', ': ' . (activeAcademicYear()->name) . '(' . (activeAcademicYear()->semester->value) . ')');

    // Instructions row
    $sheet->mergeCells('A7:F7');
    $sheet->setCellValue('A7', 'Petunjuk: Isi dengan nilai berupa angka 0-100');
    $sheet->getStyle('A7')->getFont()->setBold(true);
    $sheet->getStyle('A7')->getFont()->getColor()->setARGB('FF0000FF'); // Blue text

    // Style for headers in row 9
    $headerRange = 'A9:F9';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Border for all data and headers
    $dataRange = 'A9:F' . (9 + $this->students->count());
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Center alignment for grades columns
    $gradesRange = 'D9:F' . (9 + $this->students->count());
    $sheet->getStyle($gradesRange)->getAlignment()->setHorizontal('center');

    // Hide the ID column
    $sheet->getColumnDimension('A')->setVisible(false);

    return [
      // Set all cells to have borders
      9 => ['font' => ['bold' => true]],
    ];
  }

  public function columnWidths(): array
  {
    return [
      'A' => 8,  // ID column (hidden)
      'B' => 15, // NIM 
      'C' => 40, // Student Name
      'D' => 15, // Tugas
      'E' => 15, // UTS
      'F' => 15, // UAS
    ];
  }

  public function startCell(): string
  {
    return 'A9';
  }
}
