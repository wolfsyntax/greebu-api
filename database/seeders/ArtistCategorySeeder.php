<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ArtistCategory;
use App\Models\ArtistType;

class ArtistCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $visualArtists = ArtistCategory::firstOrCreate(['title' => 'Visual Artists', ]);
        $performingArtists = ArtistCategory::firstOrCreate(['title' => 'Performing Artists', ]);
        $literaryArtists = ArtistCategory::firstOrCreate(['title' => 'Literary Artists', ]);
        $digitalArtists = ArtistCategory::firstOrCreate(['title' => 'Digital Artists', ]);
    }
}
