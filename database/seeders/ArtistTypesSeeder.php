<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ArtistCategory;
use App\Models\ArtistType;

class ArtistTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
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

        $visualArtists = ArtistCategory::where('title','Visual Artists')->first();
        $performingArtists = ArtistCategory::where('title','Performing Artists')->first();
        $literaryArtists = ArtistCategory::where('title','Literary Artists')->first();
        $digitalArtists = ArtistCategory::where('title','Digital Artists')->first();

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
