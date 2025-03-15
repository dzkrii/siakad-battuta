<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Classroom;
use App\Models\Teacher;
use App\Models\User;
use App\Models\AcademicYear;
use App\Enums\ScheduleDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScheduleImportController extends Controller
{
  // Mapping dari nama hari di Excel ke enum value
  protected $daysOfWeek = [
    'SENIN' => 'Senin',
    'SELASA' => 'Selasa',
    'RABU' => 'Rabu',
    'KAMIS' => 'Kamis',
    'JUMAT' => 'Jumat',
    'SABTU' => 'Sabtu',
    'MINGGU' => 'Minggu',
  ];

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

      $schedules = [];
      $errors = [];
      $rowNumber = 2; // Start from row 2 (after header)

      foreach ($dataRows as $row) {
        if (empty(array_filter($row))) {
          $rowNumber++;
          continue; // Skip empty rows
        }

        // Make sure the row has the expected number of columns
        if (count($row) < 10) {
          $errors[] = "Baris {$rowNumber}: Jumlah kolom tidak sesuai format.";
          $rowNumber++;
          continue;
        }

        [
          $facultyId,
          $departmentId,
          $courseId,
          $classroomId,
          $academicYearId,
          $startTime,
          $endTime,
          $dayOfWeek,
          $quota,
          $additionalInfo
        ] = $row;

        // Validate required fields
        if (
          empty($facultyId) || empty($departmentId) || empty($courseId) ||
          empty($classroomId) || empty($academicYearId) || empty($startTime) ||
          empty($endTime) || empty($dayOfWeek) || empty($quota)
        ) {
          $errors[] = "Baris {$rowNumber}: Kolom wajib tidak boleh kosong.";
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

        if (!Course::find($courseId)) {
          $errors[] = "Baris {$rowNumber}: Course ID '{$courseId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        if (!Classroom::find($classroomId)) {
          $errors[] = "Baris {$rowNumber}: Classroom ID '{$classroomId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        if (!AcademicYear::find($academicYearId)) {
          $errors[] = "Baris {$rowNumber}: Academic Year ID '{$academicYearId}' tidak ditemukan.";
          $rowNumber++;
          continue;
        }

        // Validate quota is numeric
        if (!is_numeric($quota) || $quota <= 0) {
          $errors[] = "Baris {$rowNumber}: Kuota harus berupa angka positif.";
          $rowNumber++;
          continue;
        }

        // Validate time format
        if (!$this->validateTimeFormat($startTime) || !$this->validateTimeFormat($endTime)) {
          $errors[] = "Baris {$rowNumber}: Format waktu tidak valid. Gunakan format HH:MM (24 jam).";
          $rowNumber++;
          continue;
        }

        // Validate day of week
        $dayOfWeekUpper = strtoupper($dayOfWeek);
        if (!array_key_exists($dayOfWeekUpper, $this->daysOfWeek)) {
          $errors[] = "Baris {$rowNumber}: Hari tidak valid. Gunakan SENIN, SELASA, RABU, KAMIS, JUMAT, SABTU, atau MINGGU.";
          $rowNumber++;
          continue;
        }

        // Convert string day to the proper enum value
        $dayEnumValue = $this->daysOfWeek[$dayOfWeekUpper];
        $dayEnum = ScheduleDay::from($dayEnumValue);

        // Check for scheduling conflicts in the same classroom
        $conflictingSchedule = Schedule::where('classroom_id', $classroomId)
          ->where('day_of_week', $dayEnum)
          ->where(function ($query) use ($startTime, $endTime) {
            $query->whereBetween('start_time', [$startTime, $endTime])
              ->orWhereBetween('end_time', [$startTime, $endTime])
              ->orWhere(function ($q) use ($startTime, $endTime) {
                $q->where('start_time', '<=', $startTime)
                  ->where('end_time', '>=', $endTime);
              });
          })
          ->first();

        if ($conflictingSchedule) {
          $errors[] = "Baris {$rowNumber}: Jadwal bentrok dengan jadwal lain di ruangan yang sama.";
          $rowNumber++;
          continue;
        }

        // Create new schedule
        $schedule = new Schedule([
          'faculty_id' => $facultyId,
          'department_id' => $departmentId,
          'course_id' => $courseId,
          'classroom_id' => $classroomId,
          'academic_year_id' => $academicYearId,
          'start_time' => $startTime,
          'end_time' => $endTime,
          'day_of_week' => $dayEnum,
          'quota' => $quota,
          'additional_info' => $additionalInfo ?? null,
        ]);

        $schedules[] = $schedule;
        $rowNumber++;
      }

      // If there are errors, rollback and return error messages
      if (count($errors) > 0) {
        DB::rollBack();
        return back()->with('warning', implode("<br>", $errors));
      }

      // Save all schedules
      foreach ($schedules as $schedule) {
        $schedule->save();
      }

      DB::commit();

      return back()->with('success', 'Data jadwal berhasil diimpor: ' . count($schedules) . ' record.');
    } catch (ValidationException $e) {
      DB::rollBack();
      return back()->with('warning', $e->getMessage());
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('warning', 'Terjadi kesalahan saat memproses file: ' . $e->getMessage());
    }
  }

  private function validateTimeFormat($time)
  {
    return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time);
  }

  public function downloadTemplate()
  {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $headers = [
      'ID Fakultas (Faculty ID)',
      'ID Program Studi (Department ID)',
      'ID Mata Kuliah (Course ID)',
      'ID Kelas (Classroom ID)',
      'ID Tahun Ajaran (Academic Year ID)',
      'Waktu Mulai',
      'Waktu Berakhir',
      'Hari',
      'Kuota',
      'Informasi Tambahan'
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

    // Get teacher data with names
    $teachers = Teacher::join('users', 'teachers.user_id', '=', 'users.id')
      ->select('teachers.id', 'users.name as teacher_name')
      ->get();

    // Get course data with teacher information and department
    $courses = Course::join('teachers', 'courses.teacher_id', '=', 'teachers.id')
      ->join('users', 'teachers.user_id', '=', 'users.id')
      ->join('departments', 'courses.department_id', '=', 'departments.id')
      ->select(
        'courses.id',
        'courses.kode_matkul',
        'courses.name as course_name',
        'users.name as teacher_name',
        'departments.name as department_name',
        'courses.department_id'
      )
      ->get();

    $classrooms = Classroom::select('id', 'name')->get();
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

    // Add course reference data with teacher name and department
    $refSheet->setCellValue('G1', 'Mata Kuliah');
    $refSheet->setCellValue('G2', 'ID');
    $refSheet->setCellValue('H2', 'Kode');
    $refSheet->setCellValue('I2', 'Nama Mata Kuliah');
    $refSheet->setCellValue('J2', 'Dosen Pengampu');
    $refSheet->setCellValue('K2', 'Program Studi');
    $refSheet->setCellValue('L2', 'Department ID');
    $refSheet->setCellValue('M2', 'Format Lengkap');
    $row = 3;
    foreach ($courses as $course) {
      $refSheet->setCellValue('G' . $row, $course->id);
      $refSheet->setCellValue('H' . $row, $course->kode_matkul);
      $refSheet->setCellValue('I' . $row, $course->course_name);
      $refSheet->setCellValue('J' . $row, $course->teacher_name);
      $refSheet->setCellValue('K' . $row, $course->department_name);
      $refSheet->setCellValue('L' . $row, $course->department_id);
      $refSheet->setCellValue('M' . $row, $course->course_name . ' - ' . $course->teacher_name . ' - ' . $course->department_name);
      $row++;
    }

    // Add classroom reference data
    $refSheet->setCellValue('O1', 'Kelas');
    $refSheet->setCellValue('O2', 'ID');
    $refSheet->setCellValue('P2', 'Nama');
    $row = 3;
    foreach ($classrooms as $classroom) {
      $refSheet->setCellValue('O' . $row, $classroom->id);
      $refSheet->setCellValue('P' . $row, $classroom->name);
      $row++;
    }

    // Add academic year reference data
    $refSheet->setCellValue('R1', 'Tahun Ajaran');
    $refSheet->setCellValue('R2', 'ID');
    $refSheet->setCellValue('S2', 'Nama');
    $row = 3;
    foreach ($academicYears as $academicYear) {
      $refSheet->setCellValue('R' . $row, $academicYear->id);
      $refSheet->setCellValue('S' . $row, $academicYear->name);
      $row++;
    }

    // Add day of week reference
    $refSheet->setCellValue('U1', 'Hari');
    $refSheet->setCellValue('U2', 'Input di Excel');
    $refSheet->setCellValue('V2', 'Nilai di Database');
    $row = 3;
    foreach ($this->daysOfWeek as $input => $dbValue) {
      $refSheet->setCellValue('U' . $row, $input);
      $refSheet->setCellValue('V' . $row, $dbValue);
      $row++;
    }

    // Style reference sheet headers
    $refSheet->getStyle('A1:V2')->getFont()->setBold(true);
    $refSheet->getStyle('A1:V2')->getFill()
      ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FFCCCCCC');

    // Autosize columns in reference sheet
    foreach (range('A', 'V') as $column) {
      $refSheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Auto size columns in main sheet
    foreach (range('A', chr(65 + count($headers) - 1)) as $column) {
      $sheet->getColumnDimension($column)->setAutoSize(true);
    }

    // Add sample data if data exists
    if (
      $faculties->isNotEmpty() && $departments->isNotEmpty() &&
      $courses->isNotEmpty() && $classrooms->isNotEmpty() &&
      $academicYears->isNotEmpty()
    ) {

      // Use first course's department for the sample
      $sampleCourse = $courses->first();

      $sheet->setCellValue('A2', $faculties->first()->id);
      $sheet->setCellValue('B2', $sampleCourse->department_id); // Use matching department_id from the course
      $sheet->setCellValue('C2', $sampleCourse->id);
      $sheet->setCellValue('D2', $classrooms->first()->id);
      $sheet->setCellValue('E2', $academicYears->first()->id);
      $sheet->setCellValue('F2', '08:00');
      $sheet->setCellValue('G2', '10:30');
      $sheet->setCellValue('H2', 'SENIN');
      $sheet->setCellValue('I2', '40');
      $sheet->setCellValue('J2', 'Kelas reguler');
    }

    // Add comments with instructions
    $sheet->getComment('A2')->getText()->createTextRun('ID Fakultas. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('B2')->getText()->createTextRun('ID Program Studi. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('C2')->getText()->createTextRun('ID Mata Kuliah. Lihat sheet "Reference Data" untuk daftar lengkap mata kuliah beserta dosen pengampu dan program studi.');
    $sheet->getComment('D2')->getText()->createTextRun('ID Kelas. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('E2')->getText()->createTextRun('ID Tahun Ajaran. Lihat sheet "Reference Data" untuk daftar ID.');
    $sheet->getComment('H2')->getText()->createTextRun('Hari harus salah satu dari: SENIN, SELASA, RABU, KAMIS, JUMAT, SABTU, atau MINGGU.');

    // Return to first sheet
    $spreadsheet->setActiveSheetIndex(0);

    // Create a temporary file
    $tempFile = tempnam(sys_get_temp_dir(), 'template_schedule_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    return response()->download($tempFile, 'template_import_jadwal.xlsx')
      ->deleteFileAfterSend(true);
  }
}
