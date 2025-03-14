<?php

namespace App\Http\Controllers\Teacher;

use App\Exports\AttendanceTemplateExport;
use App\Exports\GradeTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\AttendanceImport;
use App\Imports\GradeImport;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ImportExcelController extends Controller
{
    public function index(Course $course, Classroom $classroom)
    {
        return Inertia::render('Teachers/Classrooms/Import', [
            'page_settings' => [
                'title' => "Import Data Kelas {$classroom->name} - Mata kuliah {$course->name}",
                'subtitle' => 'Import data absensi dan nilai',
            ],
            'course' => $course,
            'classroom' => $classroom,
        ]);
    }

    public function downloadAttendanceTemplate(Course $course, Classroom $classroom)
    {
        $schedule = Schedule::query()
            ->where('course_id', $course->id)
            ->where('classroom_id', $classroom->id)
            ->first();

        $students = Student::query()
            ->where('faculty_id', $classroom->faculty_id)
            ->where('department_id', $classroom->department_id)
            ->where('classroom_id', $classroom->id)
            ->wherehas('user', function ($query) {
                $query->whereHas('roles', fn($query) => $query->where('name', 'Student'));
            })
            ->whereHas('studyPlans', function ($query) use ($schedule) {
                $query->where('academic_year_id', activeAcademicYear()->id)
                    ->approved()
                    ->whereHas('schedules', fn($query) => $query->where('schedule_id', $schedule->id));
            })
            ->with(['user'])
            ->get();

        $filename = "Template_Absensi_{$course->code}_{$classroom->name}_" . date('Ymd') . ".xlsx";

        return Excel::download(
            new AttendanceTemplateExport($course, $classroom, $students),
            $filename
        );
    }

    public function importAttendances(Request $request, Course $course, Classroom $classroom)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            DB::beginTransaction();

            Excel::import(
                new AttendanceImport($course, $classroom),
                $request->file('file')
            );

            DB::commit();

            flashMessage('Data absensi berhasil diimport');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        } catch (\Exception $e) {
            DB::rollBack();
            flashMessage("Error: " . $e->getMessage(), 'error');
            return back();
        }
    }

    public function downloadGradeTemplate(Course $course, Classroom $classroom)
    {
        $schedule = Schedule::query()
            ->where('course_id', $course->id)
            ->where('classroom_id', $classroom->id)
            ->first();

        $students = Student::query()
            ->where('faculty_id', $classroom->faculty_id)
            ->where('department_id', $classroom->department_id)
            ->where('classroom_id', $classroom->id)
            ->wherehas('user', function ($query) {
                $query->whereHas('roles', fn($query) => $query->where('name', 'Student'));
            })
            ->whereHas('studyPlans', function ($query) use ($schedule) {
                $query->where('academic_year_id', activeAcademicYear()->id)
                    ->approved()
                    ->whereHas('schedules', fn($query) => $query->where('schedule_id', $schedule->id));
            })
            ->with(['user'])
            ->get();

        $filename = "Template_Nilai_{$course->code}_{$classroom->name}_" . date('Ymd') . ".xlsx";

        return Excel::download(
            new GradeTemplateExport($course, $classroom, $students),
            $filename
        );
    }

    public function importGrades(Request $request, Course $course, Classroom $classroom)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            DB::beginTransaction();

            Excel::import(
                new GradeImport($course, $classroom),
                $request->file('file')
            );

            DB::commit();

            flashMessage('Data nilai berhasil diimport');
            return to_route('teachers.classrooms.index', [$course, $classroom]);
        } catch (\Exception $e) {
            DB::rollBack();
            flashMessage("Error: " . $e->getMessage(), 'error');
            return back();
        }
    }
}
