<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Teacher;
use App\Models\User;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class CourseImportController extends Controller
{
  public function import(Request $request)
  {
    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls',
    ]);

    try {
      DB::beginTransaction();

      $file = $request->file('file');
      $spreadsheet = IOFactory::load($file->getPathname());
      $worksheet = $spreadsheet->getActiveSheet();
      $rows = $worksheet->toArray();

      // Skip header row
      $dataRows = array_slice($rows, 1);

      if (empty($dataRows)) {
        throw ValidationException::withMessages([
          'file' => ['File Excel yang diupload tidak berisi data.'],
        ]);
      }

      $courses = [];
      $errors = [];
      $rowNumber = 2; // Start from row 2 (after header)

      foreach ($dataRows as $row) {
        if (empty(array_filter($row))) {
          $rowNumber++;
          continue; // Skip empty rows
        }

        // Make sure the row has the expected number of columns
        if (count($row) < 8) {
          $errors[] = "Baris {$rowNumber}: Jumlah kolom tidak sesuai format.";
          $rowNumber++;
          continue;
        }

        [$facultyId, $departmentId, $teacherId, $academicYearId, $kodeMatkul, $name, $credit, $semester] = $row;

        // Validate required fields
        if (
          empty($facultyId) || empty($departmentId) || empty($teacherId) ||
          empty($academicYearId) || empty($kodeMatkul) || empty($name) ||
          empty($credit) || empty($semester)
        ) {
          $errors[] = "Baris {$rowNumber}: Semua kolom harus diisi.";
          $rowNumber++;
          continue;
        }

        // Validate IDs exist
        if (!Faculty::find($facultyId)) {
          $errors[] = "Baris {$rowNumber}: Faculty ID '{$facultyId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        if (!Department::find($departmentId)) {
          $errors[] = "Baris {$rowNumber}: Department ID '{$departmentId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        if (!Teacher::find($teacherId)) {
          $errors[] = "Baris {$rowNumber}: Teacher ID '{$teacherId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        if (!AcademicYear::find($academicYearId)) {
          $errors[] = "Baris {$rowNumber}: Academic Year ID '{$academicYearId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        // Validate credit and semester are numeric
        if (!is_numeric($credit) || !is_numeric($semester)) {
          $errors[] = "Baris {$rowNumber}: SKS dan Semester harus berupa angka.";
          $rowNumber++;
          continue;
        }

        // Check for duplicate course code
        $existingCourse = Course::where('kode_matkul', $kodeMatkul)->first();
        if ($existingCourse) {
          $errors[] = "Baris {$rowNumber}: Kode Mata Kuliah '{$kodeMatkul}' sudah ada dalam database.";
          $rowNumber++;
          continue;
        }

        // Create new course
        $course = new Course([
          'faculty_id' => $facultyId,
          'department_id' => $departmentId,
          'teacher_id' => $teacherId,
          'academic_year_id' => $academicYearId,
          'kode_matkul' => $kodeMatkul,
          'name' => $name,
          'credit' => $credit,
          'semester' => $semester,
        ]);

        $courses[] = $course;
        $rowNumber++;
      }

      // If there are errors, rollback and return error messages
      if (count($errors) > 0) {
        DB::rollBack();
        return back()->with('warning', implode("<br>", $errors));
      }

      // Save all courses
      foreach ($courses as $course) {
        $course->save();
      }

      DB::commit();

      return back()->with('success', 'Data mata kuliah berhasil diimpor: ' . count($courses) . ' record.');
    } catch (ValidationException $e) {
      DB::rollBack();
      return back()->with('warning', $e->getMessage());
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('warning', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
    }
  }

  public function downloadTemplate()
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
      'ID Fakultas (Faculty ID)',
      'ID Program Studi (Department ID)',
      'ID Dosen (Teacher ID)',
      'ID Tahun Ajaran (Academic Year ID)',
      'Kode Matkul',
      'Nama Mata Kuliah',
      'SKS',
      'Semester'
    ];

    foreach ($headers as $index => $header) {
      $column = chr(65 + $index); // A, B, C, etc.
      $sheet->setCellValue($column . '1', $header);

      // Style the header
      $sheet->getStyle($column . '1')->getFont()->setBold(true);
      $sheet->getStyle($column . '1')->getFill()
        ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFCCCCCC');
    }

    // Get reference data for help
    $faculties = Faculty::select('id', 'name')->get();
    $departments = Department::select('id', 'name')->get();
    // Fix: Get teachers with their names from the users table
    $teachers = Teacher::join('users', 'teachers.user_id', '=', 'users.id')
      ->select('teachers.id', 'users.name')
      ->get();
    $academicYears = AcademicYear::select('id', 'name')->get();

    // Create reference sheets
    $refSheet = $spreadsheet->createSheet();
    $refSheet->setTitle('Reference Data');

    // Add faculty reference data
    $refSheet->setCellValue('A1', 'Fakultas');
    $refSheet->setCellValue('A2', 'ID');
    $refSheet->setCellValue('B2', 'Nama');
    $row = 3;
    foreach ($faculties as $faculty) {
      $refSheet->setCellValue('A' . $row, $faculty->id);
      $refSheet->setCellValue('B' . $row, $faculty->name);
      $row++;
    }

    // Add department reference data
    $refSheet->setCellValue('D1', 'Program Studi');
    $refSheet->setCellValue('D2', 'ID');
    $refSheet->setCellValue('E2', 'Nama');
    $row = 3;
    foreach ($departments as $department) {
      $refSheet->setCellValue('D' . $row, $department->id);
      $refSheet->setCellValue('E' . $row, $department->name);
      $row++;
    }

    // Add teacher reference data
    $refSheet->setCellValue('G1', 'Dosen');
    $refSheet->setCellValue('G2', 'ID');
    $refSheet->setCellValue('H2', 'Nama');
    $row = 3;
    foreach ($teachers as $teacher) {
      $refSheet->setCellValue('G' . $row, $teacher->id);
      $refSheet->setCellValue('H' . $row, $teacher->name);
      $row++;
    }

    // Add academic year reference data
    $refSheet->setCellValue('J1', 'Tahun Ajaran');
    $refSheet->setCellValue('J2', 'ID');
    $refSheet->setCellValue('K2', 'Nama');
    $row = 3;
    foreach ($academicYears as $academicYear) {
      $refSheet->setCellValue('J' . $row, $academicYear->id);
      $refSheet->setCellValue('K' . $row, $academicYear->name);
      $row++;
    }

    // Style reference sheet headers
    $refSheet->getStyle('A1:K2')->getFont()->setBold(true);
    $refSheet->getStyle('A1:K2')->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFCCCCCC');

    // Autosize columns in reference sheet
    foreach (range('A', 'K') as $column) {
      $refSheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Auto size columns in main sheet
    foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Add sample data if data exists
    if (
      $faculties->isNotEmpty() && $departments->isNotEmpty() &&
      $teachers->isNotEmpty() && $academicYears->isNotEmpty()
    ) {
      $sheet->setCellValue('A2', $faculties->first()->id);
      $sheet->setCellValue('B2', $departments->first()->id);
      $sheet->setCellValue('C2', $teachers->first()->id);
      $sheet->setCellValue('D2', $academicYears->first()->id);
      $sheet->setCellValue('E2', 'IF-101');
      $sheet->setCellValue('F2', 'Pemrograman Dasar');
      $sheet->setCellValue('G2', '3');
      $sheet->setCellValue('H2', '1');
    }

    // Add comments with instructions
    $sheet->getComment('A2')->getText()->createTextRun('ID Fakultas. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('B2')->getText()->createTextRun('ID Program Studi. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('C2')->getText()->createTextRun('ID Dosen. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('D2')->getText()->createTextRun('ID Tahun Ajaran. Lihat sheet "Reference Data" untuk daftar ID.');

    // Return to first sheet
    $spreadsheet->setActiveSheetIndex(0);

    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'template_course_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    return response()->download($tempFile, 'template_import_mata_kuliah.xlsx')
      ->deleteFileAfterSend(true);
  }
}
