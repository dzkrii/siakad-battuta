<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $startTimes = ['08:30', '10:10', '17:00', '19:00'];
        $endTimes = ['10:00', '11:40', '18:30', '20:30'];
        $daysOfWeek = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 0; $i < 200; $i++) {
            Schedule::create([
                'faculty_id' => 1,
                'department_id' => rand(1, 3),
                'course_id' => rand(1, 50),
                'classroom_id' => rand(1, 4),
                'academic_year_id' => 1,
                'start_time' => $startTimes[array_rand($startTimes)],
                'end_time' => $endTimes[array_rand($endTimes)],
                'day_of_week' => $daysOfWeek[array_rand($daysOfWeek)],
                'quota' => rand(50, 100),
                'created_at' => Carbon::now(),
            ]);
        }
    }
}
