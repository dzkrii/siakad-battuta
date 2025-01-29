<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $teachers = [
            ['name' => 'Baginda Harahap,S.Pd.,M.Kom', 'faculty_id' => 1, 'department_id' => 1],
            ['name' => 'Auliana Nasution,S.Kom.,M.Kom.', 'faculty_id' => 1, 'department_id' => 1],
            ['name' => 'M. Furqon Siregar,S.T.,M.Kom', 'faculty_id' => 1, 'department_id' => 1],
            ['name' => 'Dinur Syahputra,S.T.,M.Kom', 'faculty_id' => 1, 'department_id' => 1],
            ['name' => 'Eka Hayana Hsb,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 1],
            ['name' => 'Fahmi Ruziq,S.T.,M.Kom', 'faculty_id' => 1, 'department_id' => 3],
            ['name' => 'M. Rhifky Wayahdi,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 3],
            ['name' => 'Dewi Wahyuni,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 3],
            ['name' => 'Subhan Hafiz Nanda,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 3],
            ['name' => 'Nurmala Sridewi, S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 3],
            ['name' => 'Ellanda Purwawijaya,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 2],
            ['name' => 'MAYANG MUGHNYANTI, S.T., M.Kom.', 'faculty_id' => 1, 'department_id' => 2],
            ['name' => 'Roy Nuary Singarimbun,S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 2],
            ['name' => 'MEISARAH RIANDINI, S.Kom., M.Kom.', 'faculty_id' => 1, 'department_id' => 2],
            ['name' => 'Chairul Imam, S.Kom.,M.Kom', 'faculty_id' => 1, 'department_id' => 2],
            // Tambahkan data lainnya sesuai daftar yang Anda berikan...
        ];

        $role = Role::firstOrCreate(['name' => 'Teacher']);

        foreach ($teachers as $teacherData) {
            $teacher = User::factory()->create([
                'name' => $teacherData['name'],
                'email' => $faker->unique()->safeEmail,
            ])->assignRole($role);

            $teacher->teacher()->create([
                'faculty_id' => $teacherData['faculty_id'],
                'department_id' => $teacherData['department_id'],
                'teacher_number' => Str::padLeft(mt_rand(0, 999999), 6, '0'),
                'academic_title' => 'Asisten Ahli',
            ]);
        }
    }
}
