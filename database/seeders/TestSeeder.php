<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\Artist;
use App\Models\Customer;
use App\Models\ArtistType;
use App\Models\Genre;
use Faker\Factory as Faker;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@gmail.com',
            'username' => 'johndoe',
            'password' => '1234567890',
        ]);

        Profile::create([
            'user_id'           => $user->id,
            'street_address'    => 'Dayangdang Street',
            'city'              => 'Naga City',
            'zip_code'          => '4400',
            'province'          => 'Camarines Sur',
            'country'           => 'Philippines',
            'business_email'    => $user->email,
            'business_name'     => $user->fullname,
            'avatar'            => 'https://via.placeholder.com/424x424.png/006644?text=Lorem',
        ])->assignRole('customers')->customer()->update([
            'name' => $user->fullname,
        ]);

        // Customer::create([
        //     'name' => $user->fullname,
        // ]);

        $artistProfile = Profile::create([
            'user_id'           => $user->id,
            'street_address'    => 'Dayangdang Street',
            'city'              => 'Naga City',
            'zip_code'          => '4400',
            'province'          => 'Camarines Sur',
            'country'           => 'Philippines',
            'business_email'    => $user->email,
            'business_name'     => $user->fullname,
            'avatar'            => 'https://via.placeholder.com/424x424.png/006644?text=Ipsum'
        ])->assignRole('artists');

        $artistType = ArtistType::first();

        $genre = Genre::all();

        $artist = Artist::create([
            'profile_id' => $artistProfile->id,
            'artist_type_id' => $artistType->id,
        ]);

        // Before
        // $artist->genres()->sync($genre);

        $artist->genres()->delete();

        // foreach ($genre as $gen) {
        //     $artist->genres()->create([
        //         'genre_title' => $gen->title,
        //     ]);
        // }


        // $genre = Genre::get();
        $genre = Genre::get()->pluck('title')->toArray();
        // Before
        // $artist->genres()->sync($this->faker->randomElements($genre->pluck('id')->toArray(), 3));

        // foreach ($genre as $gen) {
        //     $artist->genres()->create([
        //         'genre_title' => $gen->title,
        //     ]);
        // }
        $this->faker = Faker::create();
        foreach ($this->faker->randomElements($genre, 3) as $gen) {
            $artist->genres()->create([
                'genre_title' => $gen,
            ]);
        }


        // $artist->genres()->attach($genre);
    }
}
