<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Artist;
use Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $this->faker = Faker::create();

        for ($i = 1; $i <= 100; $i++) {

            $timestamp = mt_rand(1, time());

            $event = Event::create([
              'organizer_id'    => $this->faker->randomElement(Organizer::get()->pluck('id')->toArray()),
              'artist_id'       => $this->faker->randomElement(Artist::get()->pluck('id')->toArray()),
              'title'           => Str::lower($this->faker->sentence(10)),
              'description'     => Str::lower($this->faker->sentence(20)),
              'thumbnail'       => $this->faker->imageUrl(width: 424, height: 424),
              'venue'           => Str::lower($this->faker->city()),
              'lat'             => (mt_rand(10,50)/mt_getrandmax()),
              'long'            => (mt_rand(10,50)/mt_getrandmax()),
              'capacity'        => rand(10,50),
              'is_public'       => rand(1,0),
              'is_featured'     => rand(1,0),
              'is_free'         => rand(1,0),
              'status'          => 1,
              'event_date'      => date('Y-m-d',$timestamp),
              'start_time'      => date('H:i:s',$timestamp),
              'end_time'        => date('H:i:s',$timestamp)
            ]);

          }

    }
}
