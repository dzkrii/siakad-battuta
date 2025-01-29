<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(FacultySeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(AcademicYearSeeder::class);
        $this->call(FeegroupSeeder::class);
        // $this->call(TeacherSeeder::class);
        // $this->call(StudentSeeder::class);
        // $this->call(ClassroomSeeder::class);
        // $this->call(CourseSeeder::class);
        // $this->call(ScheduleSeeder::class);

        User::factory()->create([
            'name' => 'Monkey D Luffy',
            'email' => 'luffy@battuta.ac.id',
        ])->assignRole(Role::create([
            'name' => 'Admin',
        ]));

        // $operator = User::factory()->create([
        //     'name' => 'Roronoa Zoro',
        //     'email' => 'zoro@battuta.ac.id',
        // ])->assignRole(Role::create([
        //     'name' => 'Operator',
        // ]));

        // $operator->operator()->create([
        //     'faculty_id' => 1,
        //     'department_id' => 1,
        //     'employee_number' => str()->padLeft(mt_rand(0, 999999), 6, '0'),
        // ]);

        // $teacher = User::factory()->create([
        //     'name' => 'Vinsmoke Sanji',
        //     'email' => 'sanji@battuta.ac.id',
        // ])->assignRole(Role::create([
        //     'name' => 'Teacher',
        // ]));

        // $teacher->teacher()->create([
        //     'faculty_id' => 1,
        //     'department_id' => 1,
        //     'teacher_number' => str()->padLeft(mt_rand(0, 999999), 6, '0'),
        //     'academic_title' => 'Asisten Ahli',
        // ]);

        // $student = User::factory()->create([
        //     'name' => 'Usop',
        //     'email' => 'usop@battuta.ac.id',
        // ])->assignRole(Role::create([
        //     'name' => 'Student',
        // ]));

        // $student->student()->create([
        //     'faculty_id' => 1,
        //     'department_id' => 1,
        //     'fee_group_id' => rand(1, 6),
        //     'student_number' => str()->padLeft(mt_rand(0, 999999), 6, '0'),
        //     'semester' => 1,
        //     'batch' => 2025,
        // ]);
    }
}
