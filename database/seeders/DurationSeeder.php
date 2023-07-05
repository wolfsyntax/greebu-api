<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Duration;

class DurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 3 minutes
        // 1686209157 (1686209157000)
        // 1686209175 (1686209175000)

        // 1 seconds
        // 1686209199 (1686209199000)
        //
        Duration::create(['title' => '3 minutes', 'value' => 3, 'cost' => 300.00, 'duration_type' => 'min',]);
        Duration::create(['title' => '5 minutes', 'value' => 5, 'cost' => 500.00, 'duration_type' => 'min',]);
    }
}
