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

    public function downloadGradeTemplate(Course $course, Classroom $classroom)
    {
        $filename = 'template_nilai_' . $course->name . '_' . $classroom->name . '.xlsx';

        return Excel::download(
            new GradeTemplateExport($classroom->id, $course->id),
            $filename
        );
    }

    public function downloadAttendanceTemplate(Course $course, Classroom $classroom)
    {
        $filename = 'template_absensi_' . $course->name . '_' . $classroom->name . '.xlsx';

        return Excel::download(
            new AttendanceTemplateExport($classroom->id, $course->id),
            $filename
        );
    }

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
}
