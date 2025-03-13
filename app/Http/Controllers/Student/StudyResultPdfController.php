<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\StudyResult;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class StudyResultPdfController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            // new Middleware('checkActiveAcademicYear'),
            // new Middleware('checkFeeStudent'),
        ];
    }

    public function __invoke(StudyResult $studyResult)
    {
        // Check if the logged-in student owns this study result
        if ($studyResult->student_id !== auth()->user()->student->id) {
            abort(403, 'Unauthorized action.');
        }

        // Set locale to Indonesian
        Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

        // Get the student data
        $student = auth()->user()->student;

        // Get courses and grades
        $grades = $studyResult->grades()->with('course')->get();

        // Calculate total SKS and total T
        $totalSks = $grades->sum('course.credit');
        $totalT = $grades->sum(function ($grade) {
            return $grade->weight_of_value * $grade->course->credit;
        });

        // Tentukan Kaprodi berdasarkan department/prodi
        $kaprodi = $this->getKaprodi($student->department->name);

        $pdf = PDF::loadView('pdf.study-result', [
            'student' => $student,
            'studyResult' => $studyResult,
            'grades' => $grades,
            'totalSks' => $totalSks,
            'totalT' => $totalT,
            'date' => Carbon::now()->isoFormat('D MMMM Y'),
            'kaprodi' => $kaprodi
        ]);

        return $pdf->stream("KHS_{$student->student_number}_Semester_{$studyResult->semester}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . "KHS_{$student->student_number}_Semester_{$studyResult->semester}.pdf" . '"',
        ]);
    }

    /**
     * Mendapatkan nama Kaprodi berdasarkan nama prodi
     *
     * @param string $departmentName
     * @return array
     */
    private function getKaprodi($departmentName)
    {
        $kaprodiList = [
            'Informatika' => 'Baginda Harahap, S.Pd., M.Kom.',
            'Sistem Informasi' => 'Fahmi Ruziq, S.T., M.Kom.',
            'Teknologi Informasi' => 'Aripin Rambe, S.Kom., M.Kom.',
            'Kewirausahaan' => 'Atika Aini Nasution, S.E., M.M.',
            'Akuntansi' => 'Fhikry Ahmad H. Srg., S.E., M.Ak.',
            'Manajemen' => 'Amril, S.Kom., M.M.',
            'Hukum' => 'Junaidi Lubis, S.H., M.H.',
            'PGSD' => 'Nur Wahyuni, S.Pd., M.Pd.',
            'PGPAUD' => 'Putri Sari Ulfa, S.Pd., M.Pd.',
        ];

        // Default Kaprodi jika prodi tidak ditemukan dalam daftar
        $defaultKaprodi = 'Fahmi Ruziq, S.Kom., M.Kom.';

        return $kaprodiList[$departmentName] ?? $defaultKaprodi;
    }
}
