<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SongType;

class SongTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        SongType::create(['name' => 'Happy',]);
        SongType::create(['name' => 'Sad',]);
        SongType::create(['name' => 'Romance',]);
    }
}
