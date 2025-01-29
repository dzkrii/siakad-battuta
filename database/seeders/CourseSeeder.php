<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Faker\Factory as Faker;

class CourseSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 50; $i++) {
            Course::create([
                'faculty_id' => 1,
                'department_id' => rand(1, 3),
                'teacher_id' => rand(1, 15),
                'academic_year_id' => 1,
                'code' => strtoupper(Str::random(6)),
                'name' => $faker->sentence(3),
                'credit' => rand(1, 3),
                'semester' => rand(1, 6),
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
