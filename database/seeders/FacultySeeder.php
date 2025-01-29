<?php

namespace Database\Seeders;

use App\Models\Faculty;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faculty = [
            [
                'name' => 'Fakultas Teknologi',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Fakultas Teknologi'),
                'created_at' => now(),
            ],
            [
                'name' => 'Fakultas Ekonomi dan Bisnis',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Fakultas Ekonomi dan Bisnis'),
                'created_at' => now(),
            ],
            [
                'name' => 'Fakultas Hukum dan Pendidikan',
                'code' => rand(6, 999999),
                'slug' => str()->slug('Fakultas Hukum dan Pendidikan'),
                'created_at' => now(),
            ],
        ];
        Faculty::insert($faculty);
    }
}
