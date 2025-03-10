<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Student;
use App\Traits\CalculatesFinalScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\GradesImport;
use App\Imports\AttendancesImport;
use App\Exports\GradeTemplateExport;
use App\Exports\AttendanceTemplateExport;
use App\Imports\MultipleGradesImport;
use App\Imports\CourseSchedulesGradesImport;
use App\Exports\CourseSchedulesTemplateExport;
use App\Imports\CourseSchedulesAttendancesImport;
use App\Exports\CourseSchedulesAttendanceTemplateExport;
use Illuminate\Support\Facades\Log;

class ImportExcelController extends Controller
{
    use CalculatesFinalScore;

    public function index(Course $course, Classroom $classroom)
    {
        return Inertia::render('Teachers/Classrooms/ImportExcel', [
            'page_settings' => [
                'title' => "Import Excel - Kelas {$classroom->name} - Mata kuliah {$course->name}",
                'subtitle' => 'Upload nilai dan absensi melalui Excel',
            ],
            'course' => $course,
            'classroom' => $classroom,
        ]);
    }

    // Template Nilai
    public function downloadGradeTemplate(Course $course, Classroom $classroom)
    {
        $filename = 'template_nilai_' . $course->name . '_' . $classroom->name . '.xlsx';

        return Excel::download(
            new GradeTemplateExport($classroom->id, $course->id),
            $filename
        );
    }

    public function downloadCourseSchedulesTemplate(Course $course)
    {
        try {
            $filename = 'template_nilai_semua_jadwal_' . $course->name . '.xlsx';

            return Excel::download(
                new CourseSchedulesTemplateExport($course->id),
                $filename
            );
        } catch (\Throwable $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            flashMessage('Terjadi kesalahan saat mengunduh template: ' . $e->getMessage(), 'error');
            return back();
        }
    }

    // Template Absensi
    public function downloadAttendanceTemplate(Course $course, Classroom $classroom)
    {
        $filename = 'template_absensi_' . $course->name . '_' . $classroom->name . '.xlsx';

        return Excel::download(
            new AttendanceTemplateExport($classroom->id, $course->id),
            $filename
        );
    }

    public function downloadCourseSchedulesAttendanceTemplate(Course $course)
    {
        $filename = 'template_absensi_semua_jadwal_' . $course->name . '.xlsx';

        return Excel::download(
            new CourseSchedulesAttendanceTemplateExport($course->id),
            $filename
        );
    }

    // Import Nilai
    public function importGrades(Course $course, Classroom $classroom, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            DB::beginTransaction();

            Log::info('Starting multiple grades import');

            $import = new MultipleGradesImport($classroom->id, $course->id);
            Excel::import($import, $request->file('file'));

            DB::commit();

            Log::info('Import completed successfully');
            flashMessage('Data nilai berhasil diimport');
            return redirect()->route('teachers.classrooms.index', [$course, $classroom]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Import Grades Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            flashMessage('Terjadi kesalahan: ' . $e->getMessage(), 'error');
            return back();
        }
    }

    public function importCourseSchedulesGrades(Course $course, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            Log::info('Starting course schedules grades import with course ID validation', [
                'course_id' => $course->id,
                'course_name' => $course->name
            ]);

            $import = new CourseSchedulesGradesImport($course->id);
            Excel::import($import, $request->file('file'));

            $results = $import->getImportResults();
            Log::info('Course schedules import results', $results);

            // Buat pesan sukses yang menampilkan informasi nilai, absensi, dan mismatch
            $successMessage = "Import berhasil: {$results['grades_success']} nilai baru, {$results['grades_updated']} nilai diperbarui";

            // Tambahkan informasi absensi jika ada
            if ($results['attendance_success'] > 0) {
                $successMessage .= ", {$results['attendance_success']} data absensi";
            }

            // Tambahkan informasi baris yang dilewati karena course ID tidak cocok
            if (isset($results['course_mismatch']) && $results['course_mismatch'] > 0) {
                $successMessage .= ", {$results['course_mismatch']} baris berbeda mata kuliah dilewati";
            }

            // Tambahkan informasi error jika ada
            if ($results['grades_skipped'] > 0 || $results['error'] > 0) {
                $successMessage .= ", {$results['grades_skipped']} dilewati, {$results['error']} error";
            }

            flashMessage($successMessage);
            return back();
        } catch (\Throwable $e) {
            Log::error('Import Course Schedules Grades Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            flashMessage('Terjadi kesalahan: ' . $e->getMessage(), 'error');
            return back();
        }
    }

    // Import Absensi
    public function importAttendances(Course $course, Classroom $classroom, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            DB::beginTransaction();

            Excel::import(
                new AttendancesImport($classroom->id, $course->id),
                $request->file('file')
            );

            DB::commit();

            flashMessage('Data absensi berhasil diimport');
            return redirect()->route('teachers.classrooms.index', [$course, $classroom]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Import Attendance Error: ' . $e->getMessage());
            flashMessage('Terjadi kesalahan: ' . $e->getMessage(), 'error');
            return back();
        }
    }

    public function importCourseSchedulesAttendances(Course $course, Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            Log::info('Starting course schedules attendances import');

            $import = new CourseSchedulesAttendancesImport($course->id);
            Excel::import($import, $request->file('file'));

            $results = $import->getImportResults();
            Log::info('Course schedules attendances import results', $results);

            $successMessage = "Import absensi berhasil: {$results['success']} absensi baru";
            if ($results['skipped'] > 0 || $results['error'] > 0) {
                $successMessage .= ", {$results['skipped']} dilewati, {$results['error']} error";
            }

            flashMessage($successMessage);
            return back();
        } catch (\Throwable $e) {
            Log::error('Import Course Schedules Attendances Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            flashMessage('Terjadi kesalahan: ' . $e->getMessage(), 'error');
            return back();
        }
    }
}
