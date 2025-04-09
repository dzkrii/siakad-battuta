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

    $sheet->setCellValue('C6', 'Tahun Akademik');
    $sheet->setCellValue('D6', ': ' . (activeAcademicYear()->name) . ' (' . (activeAcademicYear()->semester->value) . ')');
    // $sheet->mergeCells('C6:D6');

    // Instructions row
    // $sheet->mergeCells('B7:T7');
    $sheet->setCellValue('B7', '');
    $sheet->getStyle('B7')->getFont()->setBold(true);
    $sheet->getStyle('B7')->getFont()->getColor()->setARGB('FF0000FF'); // Blue text

    // Add NB note
    $sheet->mergeCells('B8:T8');
    $sheet->setCellValue('B8', 'NB: Bagi mahasiswa yang tidak ada namanya di daftar hadir ini namun mahasiswa tersebut masuk kedalam kelas, mohon konfirmasi ke akademik terlebih dahulu. Jangan menambahkan nama mahasiswa secara manual');
    $sheet->getStyle('B8')->getFont()->setBold(true);
    $sheet->getStyle('B8')->getFont()->getColor()->setARGB('FFFF0000'); // Red text
    $sheet->getStyle('B8')->getAlignment()->setHorizontal('left');

    // Add "Pertemuan" merged cell above the attendance columns
    $sheet->mergeCells('E9:T9');
    $sheet->setCellValue('E9', 'Pertemuan');
    $sheet->getStyle('E9')->getFont()->setBold(true);
    $sheet->getStyle('E9')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('E9')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "No" merged cell
    $sheet->mergeCells('B9:B10');
    $sheet->setCellValue('B9', 'No');
    $sheet->getStyle('B9')->getFont()->setBold(true);
    $sheet->getStyle('B9')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('B9')->getAlignment()->setVertical('center');
    $sheet->getStyle('B9')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "NIM" merged cell
    $sheet->mergeCells('C9:C10');
    $sheet->setCellValue('C9', 'NIM');
    $sheet->getStyle('C9')->getFont()->setBold(true);
    $sheet->getStyle('C9')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('C9')->getAlignment()->setVertical('center');
    $sheet->getStyle('C9')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Add "Nama Mahasiswa" merged cell
    $sheet->mergeCells('D9:D10');
    $sheet->setCellValue('D9', 'Nama Mahasiswa');
    $sheet->getStyle('D9')->getFont()->setBold(true);
    $sheet->getStyle('D9')->getAlignment()->setHorizontal('center');
    $sheet->getStyle('D9')->getAlignment()->setVertical('center');
    $sheet->getStyle('D9')->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Style for headers in row 10 (now only for attendance columns)
    $headerRange = 'E10:T10';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($headerRange)->getFill()
      ->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFD3D3D3'); // Light gray background

    // Border for all data and headers
    $dataRange = 'A10:T' . (10 + $this->students->count());
    $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Add border for the merged cells in row 9
    $sheet->getStyle('B9:T9')->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_THIN);

    // Set row height for student data rows
    for ($i = 11; $i <= 10 + $this->students->count(); $i++) {
      $sheet->getRowDimension($i)->setRowHeight(25);
    }

    // Center alignment for No Column
    $noRange = 'B11:B' . (10 + $this->students->count());
    $sheet->getStyle($noRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($noRange)->getAlignment()->setVertical('center');

    // Center alignment for NIM Column (both horizontal and vertical)
    $nimRange = 'C11:C' . (10 + $this->students->count());
    $sheet->getStyle($nimRange)->getAlignment()->setHorizontal('center');
    $sheet->getStyle($nimRange)->getAlignment()->setVertical('center');

    // Left alignment for Nama Mahasiswa Column (horizontal) and center (vertical)
    $namaRange = 'D11:D' . (10 + $this->students->count());
    $sheet->getStyle($namaRange)->getAlignment()->setHorizontal('left');
    $sheet->getStyle($namaRange)->getAlignment()->setVertical('center');

    // Center alignment for all attendance columns
    $attendanceRange = 'E11:T' . (10 + $this->students->count());
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
      9 => ['font' => ['bold' => true]],
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
    return 'A10';
  }
}
