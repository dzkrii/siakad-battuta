<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faculty>
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $name = $this->faker->unique()->randomElement([
                'Fakultas Teknologi',
                'Fakultas Ekonomi dan Bisnis',
                'Fakultas Hukum dan Pendidikan',
            ]),
            'slug' => str()->slug($name),
            'code' => str()->random(6),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($faculty) {
            $departments = match ($faculty->name) {
                'Fakultas Teknologi' => [
                    ['name' => $name = 'Informatika', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'Sistem Informasi', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'Teknologi Informasi', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                ],
                'Fakultas Ekonomi dan Bisnis' => [
                    ['name' => $name = 'Akuntansi', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'Kewirausahaan', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'Manajemen', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                ],
                'Fakultas Hukum dan Pendidikan' => [
                    ['name' => $name = 'Hukum', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'PGSD', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                    ['name' => $name = 'PGPAUD', 'slug' => str()->slug($name), 'code' => str()->random(6)],
                ],
                default => [],
            };

            foreach ($departments as $deparment) {
                $faculty->departments()->create([
                    'name' => $deparment['name'],
                    'slug' => $deparment['slug'],
                    'code' => $deparment['code'],
                ]);
            }
        });
    }
}
