<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\ArtistType;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserPH;
use App\Models\Profile;
use App\Models\Artist;
use App\Models\ArtistCategory;
use App\Models\Genre;
use App\Models\OrganizerStaff;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    protected $faker;

    // public function __construct()
    // {
    //     $this->faker = $this->withFaker();
    // }

    // protected function withFaker()
    // {
    //     return Container::getInstance()->make(Generator::class);
    // }
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            EventTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            StaffRoleSeeder::class,
            PurposeSeeder::class,
            SongTypeSeeder::class,
            SupportedLanguageSeeder::class,
            ArtistCategorySeeder::class,
            ArtistTypeSeeder::class,
            ArtistTypesCategorySeeder::class,
            GenreSeeder::class,
            CancellationReasonSeeder::class,
            PlansSeeders::class,
            BankCardSeeder::class,
            PaymentSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            DurationSeeder::class,
            // TestSeeder::class,
            // OrganizerSeeder::class,
            // EventSeeder::class,
            ServiceProviderSeeder::class,
        ]);

        $this->faker = Faker::create();

        // UserPH::factory()->count(20)->create()->each(function ($user) {

        //     $this->faker->addProvider(new \Faker\Provider\en_PH\Address($this->faker));

        //     $profile = Profile::create([
        //         'user_id'           => $user->id,
        //         'street_address'    => $this->faker->barangay(),
        //         'avatar'            => 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
        //         'business_email'    => $user->email,
        //         'business_name'     => $user->full_name,
        //         'city'              => $this->faker->city, // fake()->city(),
        //         'zip_code'          => $this->faker->postCode, // fake()->postcode(),
        //         'phone'             => $user->phone,
        //         'province'          => $this->faker->province(),
        //         'country'           => 'Philippines',
        //     ])->assignRole('artists');

        //     $artist = Artist::create([
        //         'profile_id'        => $profile->id,
        //         'artist_type_id'    => $this->faker->randomElement(ArtistType::get()->pluck('id')->toArray()),
        //     ]);

        //     // $genre = Genre::get();
        //     $genre = Genre::get()->pluck('title')->toArray();
        //     // Before
        //     // $artist->genres()->sync($this->faker->randomElements($genre->pluck('id')->toArray(), 3));

        //     // foreach ($genre as $gen) {
        //     //     $artist->genres()->create([
        //     //         'genre_title' => $gen->title,
        //     //     ]);
        //     // }

        //     foreach ($this->faker->randomElements($genre, 3)  as $gen) {
        //         $artist->genres()->create([
        //             'genre_title' => $gen,
        //         ]);
        //     }

        //     // $artist->genres()->attach($this->faker->randomElements($genre->pluck('title')->toArray(), 3));
        // });

        // User::factory()->count(20)->create()->each(function ($user) {
        //     $profile = Profile::create([
        //         'user_id'           => $user->id,
        //         'street_address'    => $this->faker->streetAddress(),
        //         // 'avatar'            => $this->faker->imageUrl(width: 424, height: 424),
        //         'avatar'            => 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
        //         'business_email'    => $user->email,
        //         'business_name'     => $user->full_name,
        //         'city'              => $this->faker->city, // fake()->city(),
        //         'zip_code'          => $this->faker->postCode, // fake()->postcode(),
        //         'phone'             => $user->phone,
        //         'province'          => $this->faker->state, // fake()->state(),
        //         'country'           => $this->faker->country, // fake()->country(),
        //     ])->assignRole('artists');

        //     $artist = Artist::create([
        //         'profile_id'        => $profile->id,
        //         'artist_type_id'    => $this->faker->randomElement(ArtistType::get()->pluck('id')->toArray()),
        //     ]);

        //     // $genre = Genre::get();
        //     $genre = Genre::get()->pluck('title')->toArray();
        //     // Before
        //     // $artist->genres()->sync($this->faker->randomElements($genre->pluck('id')->toArray(), 3));

        //     // foreach ($genre as $gen) {
        //     //     $artist->genres()->create([
        //     //         'genre_title' => $gen->title,
        //     //     ]);
        //     // }

        //     foreach ($this->faker->randomElements($genre, 3)  as $gen) {
        //         $artist->genres()->create([
        //             'genre_title' => $gen,
        //         ]);
        //     }

        //     //$artist->genres()->attach($this->faker->randomElements($genre->pluck('title')->toArray(), 3));
        // });

        // User::factory()->count(100)->create()->each(function ($user) {
        //     $profile = Profile::create([
        //         'user_id'           => $user->id,
        //         'street_address'    => $this->faker->streetAddress(),
        //         // 'avatar'            => $this->faker->imageUrl(width: 424, height: 424),
        //         'avatar'            => 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT),
        //         'business_email'    => $user->email,
        //         'business_name'     => $user->full_name,
        //         'city'              => $this->faker->city,
        //         'zip_code'          => $this->faker->postcode,
        //         'phone'             => $user->phone,
        //         'province'          => $this->faker->state(),
        //         'country'           => $this->faker->country(),
        //     ])->assignRole('artists');

        //     $artist = Artist::create([
        //         'profile_id'            => $profile->id,
        //         'artist_type_id'        => $this->faker->randomElement(ArtistType::get()->pluck('id')->toArray()),
        //         'accept_request'        => true,
        //     ]);

        //     // $genre = Genre::get();
        //     $genre = Genre::get()->pluck('title')->toArray();
        //     // Before
        //     // $artist->genres()->sync($this->faker->randomElements($genre->pluck('id')->toArray(), 3));

        //     // foreach ($genre as $gen) {
        //     //     $artist->genres()->create([
        //     //         'genre_title' => $gen->title,
        //     //     ]);
        //     // }

        //     foreach ($this->faker->randomElements($genre, 3)  as $gen) {
        //         $artist->genres()->create([
        //             'genre_title' => $gen,
        //         ]);
        //     }

        //     // $artist->genres()->attach($this->faker->randomElements($genre->pluck('title')->toArray(), 3));
        // });
    }
}
