<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYears = [
            [
                'name' => '2024/2025',
                'slug' => str()->slug('2024/2025'),
                'start_date' => '2024-08-01',
                'end_date' => '2025-07-31',
                'semester' => 'Ganjil',
                'is_active' => true,
                'created_at' => now(),
            ],
        ];

        DB::table('academic_years')->insert($academicYears);
    }
}
