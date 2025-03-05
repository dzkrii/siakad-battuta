<?php

namespace App\Traits;

use App\Models\Attendance;
use App\Models\Grade;

trait CalculatesFinalScore
{
    public function getAttendanceCount(int $studentId, int $courseId, int $classroomId): int
    {
        return Attendance::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('classroom_id', $classroomId)
            ->whereBetween('section', [1, 16])
            ->active()
            ->count();
    }

    public function getGradeCount(int $studentId, int $courseId, int $classroomId, string $category): int
    {
        $grade = Grade::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('classroom_id', $classroomId)
            ->where('category', $category);

        if (in_array($category, ['uts', 'uas', 'tugas'])) {
            $grade->whereNull('section');
        }

        return $grade->sum('grade');
    }

    public function calculateAttendancePercentage(int $attendanceCount, int $totalSessions = 16): float
    {
        return round(($attendanceCount / $totalSessions) * 10, 2);
    }

    public function calculateTaskPercentage(int $taskCount): float
    {
        return round($taskCount * 0.50, 2);
    }

    public function calculateUTSPercentage(int $utsCount): float
    {
        return round($utsCount * 0.15, 2);
    }

    public function calculateUASPercentage(int $uasCount): float
    {
        return round($uasCount * 0.25, 2);
    }

    public function calculateFinalScore(
        float $attendancePercentage,
        float $taskPercentage,
        float $utsPercentage,
        float $uasPercentage
    ): float {
        return round($attendancePercentage + $taskPercentage + $utsPercentage + $uasPercentage, 2);
    }

    public function getWeight(string $letterGrade): float
    {
        $gradePoints = [
            'A' => 4.00,
            'B+' => 3.50,
            'B' => 3.00,
            'C+' => 2.50,
            'C' => 2.00,
            'D' => 1.00,
            'E' => 0.00,
        ];

        return $gradePoints[$letterGrade] ?? 0.00;
    }

    public function convertScoreToGPA($score)
    {
        if ($score >= 80) return 4.00;
        if ($score >= 75) return 3.50;
        if ($score >= 71) return 3.00;
        if ($score >= 56) return 2.50;
        if ($score >= 51) return 2.00;
        if ($score >= 40) return 1.50;
        return 0.00;
    }
}
