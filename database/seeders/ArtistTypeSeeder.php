<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ArtistType;

class ArtistTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        ArtistType::create(['title' => 'Full band',]);
        ArtistType::create(['title' => 'Acoustic Band',]);
        ArtistType::create(['title' => 'Solo Artist',]);
        ArtistType::create(['title' => 'Duo Artist',]);
    }
}
