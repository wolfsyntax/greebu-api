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
            ['title' => 'Rock', 'description' => ''],
            ['title' => 'Hip-hop/Rap', 'description' => ''],
            ['title' => 'Pop', 'description' => ''],
            ['title' => 'Reggae', 'description' => ''],
            ['title' => 'Metal', 'description' => ''],
            ['title' => 'R&B/Soul', 'description' => ''],
            ['title' => 'Country Rock', 'description' => ''],
            ['title' => 'Others', 'description' => ''],
        ];

        foreach ($genres as $genre) {
            # code...
            Genre::create($genre);
        }
    }
}
