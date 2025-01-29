<?php

namespace Database\Seeders;

use App\Models\FeeGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeegroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $feeGroups = [
            [
                'group' => 1,
                'amount' => 4500000,
                'created_at' => now(),
            ],
        ];

        DB::table('fee_groups')->insert($feeGroups);
    }
}
