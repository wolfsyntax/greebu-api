<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\ArtistType;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use App\Models\Artist;
use App\Models\Genre;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EventTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            PurposeSeeder::class,
            SongTypeSeeder::class,
            SupportedLanguageSeeder::class,
            ArtistTypeSeeder::class,
            GenreSeeder::class,
            CancellationReasonSeeder::class,
            PlansSeeders::class,
            BankCardSeeder::class,
            PaymentSeeder::class,
            CountrySeeder::class,
            DurationSeeder::class,
            TestSeeder::class,
        ]);

        User::factory()->count(100)->create()->each(function ($user) {
            $profile = Profile::create([
                'user_id'           => $user->id,
                'street_address'    => fake()->streetAddress(),
                'avatar'            => fake()->imageUrl(width: 424, height: 424),
                'business_email'    => $user->email,
                'business_name'     => $user->full_name,
                'city'              => fake()->city(),
                'zip_code'          => fake()->postcode(),
                'phone'             => $user->phone,
                'province'          => fake()->state(),
                'country'           => fake()->country(),
            ])->assignRole('artists');

            $artist = Artist::create([
                'profile_id' => $profile->id,
                'artist_type_id' => fake()->randomElement(ArtistType::get()->pluck('id')->toArray()),
            ]);

            $genre = Genre::get();
            $artist->genres()->sync(fake()->randomElements($genre->pluck('id')->toArray(), 3));
        });
    }
}
