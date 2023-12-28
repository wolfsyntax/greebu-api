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
        $visualArtists = ArtistCategory::create(['title' => 'Visual Artists', ]);
        $performingArtists = ArtistCategory::create(['title' => 'Performing Artists', ]);
        $literaryArtists = ArtistCategory::create(['title' => 'Literary Artists', ]);
        $digitalArtists = ArtistCategory::create(['title' => 'Digital Artists', ]);

        $visual_arts = ['Painter', 'Sculptors', 'Photographers', 'Mural Painter',];
        $literary_arts = ['Writers', 'Poets',];
        $digital_arts = ['Graphics Designers', 'Animators', 'Content Writer', 'Copywriters', 'Content creator', ];
        $performing_arts = ['Disk Jockey', 'Guitarist', 'Vocalist', 'Bassist', 'Drummer', 'Keyboardist', 'Dancers', 'Actors', 'Spoken  Word Artists', 'Host', 'Cosplayer',];

        $band = ArtistType::where('title', 'Full band')->first();

        if ($band) $band->update([
            'category_id'   => $performingArtists->id,
            'title' => 'Band',
        ]);

        $solo = ArtistType::where('title', 'Solo Artist')->first();

        if ($solo) $solo->update([
            'category_id'   => $performingArtists->id,
        ]);

        foreach ($visual_arts as $value) {
            ArtistType::create([
                'title'         => $value,
                'category_id'   => $visualArtists->id,
            ]);
        }

        foreach ($literary_arts as $value) {
            ArtistType::create([
                'title'         => $value,
                'category_id'   => $literaryArtists->id,
            ]);
        }

        foreach ($performing_arts as $value) {
            ArtistType::create([
                'title'         => $value,
                'category_id'   => $performingArtists->id,
            ]);
        }

        foreach ($digital_arts as $value) {
            ArtistType::create([
                'title'         => $value,
                'category_id'   => $digitalArtists->id,
            ]);
        }
    }
}
