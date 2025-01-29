<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'faculty_id' => 1,
                'name' => 'Informatika',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Informatika'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 1,
                'name' => 'Teknologi Informasi',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Teknologi Informasi'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 1,
                'name' => 'Sistem Informasi',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Sistem Informasi'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 2,
                'name' => 'Akuntansi',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Akuntansi'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 2,
                'name' => 'Kewirausahaan',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Kewirausahaan'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 2,
                'name' => 'Manajemen',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Manajemen'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 3,
                'name' => 'Hukum',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Hukum'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 3,
                'name' => 'PGSD',
                'code' => rand(6, 999999),
                'slug' => str()->slug('PGSD'),
                'created_at' => now(),
            ],
            [
                'faculty_id' => 3,
                'name' => 'PGPAUD',
                'code' => rand(6, 999999),
                'slug' => str()->slug('PGPAUD'),
                'created_at' => now(),
            ],
        ];

        DB::table('departments')->insert($departments);
    }
}
