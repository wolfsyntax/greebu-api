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
            'business_email'    => $user->business_email,
            'business_name'     => $user->fullname,
            'avatar'            => fake()->imageUrl(width: 424, height: 424, category: 'cats'),
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
            'business_email'    => $user->business_email,
            'business_name'     => $user->fullname,
            'avatar'            => fake()->imageUrl(width: 424, height: 424, category: 'avatar')
        ])->assignRole('artists');

        $artistType = ArtistType::first();

        $genre = Genre::all();

        $artist = Artist::create([
            'profile_id' => $artistProfile->id,
            'artist_type_id' => $artistType->id,
        ]);

        $artist->genres()->sync($genre);
    }
}
