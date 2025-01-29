<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = [
            ['faculty_id' => 1, 'department_id' => 1, 'academic_year_id' => 1, 'name' => 'IF-3-PAGI'],
            ['faculty_id' => 1, 'department_id' => 1, 'academic_year_id' => 1, 'name' => 'IF-3-SORE'],
            ['faculty_id' => 1, 'department_id' => 2, 'academic_year_id' => 1, 'name' => 'SI-3-PAGI'],
            ['faculty_id' => 1, 'department_id' => 3, 'academic_year_id' => 1, 'name' => 'TI-3-PAGI'],
        ];

        foreach ($classrooms as $classroomData) {
            Classroom::create([
                'faculty_id' => $classroomData['faculty_id'],
                'department_id' => $classroomData['department_id'],
                'academic_year_id' => $classroomData['academic_year_id'],
                'name' => $classroomData['name'],
                'slug' => Str::slug($classroomData['name']),
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
