<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

use App\Models\Organizer;
use App\Models\User;
use App\Models\Profile;

class OrganizerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $this->faker = Faker::create();

        User::factory()->count(20)->create()->each(function ($user) {

            $profile = Profile::create([
                'user_id'           => $user->id,
                'street_address'    => $this->faker->streetAddress(),
                'avatar'            => $this->faker->imageUrl(width: 424, height: 424),
                'business_email'    => $user->email,
                'business_name'     => $user->full_name,
                'city'              => $this->faker->city,
                'zip_code'          => $this->faker->postCode,
                'phone'             => $user->phone,
                'province'          => $this->faker->state,
                'country'           => $this->faker->country,
            ]);

            $organizer = Organizer::create([
                'profile_id'        => $profile->id,
                'first_name'        => $user->first_name,
                'last_name'         => $user->last_name,
                'email'             => $user->email,
                'phone_alt'         => '',
                'bio'               => '',
                'facebook_url'      => ''
            ]);

        });


    }
}
