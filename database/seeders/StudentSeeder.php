<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();
        $role = Role::firstOrCreate(['name' => 'Student']);

        for ($i = 0; $i < 20; $i++) {
            $student = User::factory()->create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
            ])->assignRole($role);

            $student->student()->create([
                'faculty_id' => 1,
                'department_id' => rand(1, 3),
                'fee_group_id' => 1,
                'student_number' => Str::padLeft(mt_rand(0, 999999), 6, '0'),
                'semester' => 1,
                'batch' => 2025,
            ]);
        }
    }
}
