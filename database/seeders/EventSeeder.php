<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Artist;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $faker = Faker::create();

        for ($i = 1; $i <= 100; $i++) {

            $timestamp = mt_rand(1, time());

            Event::create([
                'organizer_id'      => $faker->randomElement(Organizer::get()->pluck('id')->toArray()),
                // 'artist_id'       => $this->faker->randomElement(Artist::get()->pluck('id')->toArray()),
                'event_types_id'    => $faker->randomElement(\App\Models\EventType::get()->pluck('id')->toArray()),
                'cover_photo'       => $faker->imageUrl(width: 424, height: 424),
                'event_name'        => Str::lower($faker->sentence(10)),
                // 'location'          => Str::lower($faker->city()),
                'street_address'    => Str::lower($faker->streetAddress()),
                'barangay'          => Str::lower($faker->streetName()),
                'city'              => Str::lower($faker->city),
                'province'          => Str::lower($faker->state),
                'audience'          => rand(1, 0),

                'start_date'        => now()->add(mt_rand(5, 10), 'days'), //date('Y-m-d', $timestamp),
                'end_date'          => now()->add(mt_rand(15, 45), 'days'), //date('Y-m-d', $timestamp),

                'start_time'        => date('H:i:s', mt_rand(1, time())), //date('H:i:s', $timestamp),
                'end_time'          => date('H:i:s', mt_rand(5, time())),

                'description'       => Str::lower($faker->sentence(20)),


                'lat'               => '0.00',
                'long'              => '0.00',
                'capacity'          => 0,

                'is_featured'       => rand(1, 0),
                'is_free'           => false,
                'status'            => 'draft',
                'review_status'     => 'accepted',
            ]);
        }
    }
}
