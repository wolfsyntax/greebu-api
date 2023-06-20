<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Genre;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $genres = [
            ['title' => 'Popular music', 'description' => ''],
            ['title' => 'Pop music', 'description' => ''],
            ['title' => 'Rock', 'description' => ''],
            ['title' => 'Jazz', 'description' => ''],
            ['title' => 'Hip hop music', 'description' => ''],
            ['title' => 'Electronic music', 'description' => ''],
            ['title' => 'Country music', 'description' => ''],
            ['title' => 'Classical music', 'description' => ''],
            ['title' => 'Rhythm and Blues', 'description' => ''],
            ['title' => 'Heavy Metal', 'description' => ''],
            ['title' => 'Alternative Rock', 'description' => ''],
            ['title' => 'Blues', 'description' => ''],
            ['title' => 'Reggae', 'description' => ''],
            ['title' => 'Funk', 'description' => ''],
            ['title' => 'Soul music', 'description' => ''],
            ['title' => 'Folk music', 'description' => ''],
            ['title' => 'Techno', 'description' => ''],
            ['title' => 'K-pop', 'description' => ''],
            ['title' => 'Classic Rock', 'description' => ''],
        ];

        foreach ($genres as $genre) {
            # code...
            Genre::create($genre);
        }
    }
}
