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
        $faker = Faker::create();

        User::factory()->count(20)->create()->each(function ($user) use ($faker) {

            $profile = Profile::create([
                'user_id'           => $user->id,
                'street_address'    => $faker->streetAddress(),
                'avatar'            => 'https://ui-avatars.com/api/?name=' . $user->fullname . '&rounded=true&bold=true&size=424&background=' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT), //$faker->imageUrl(width: 424, height: 424),
                'business_email'    => $user->email,
                'business_name'     => $user->full_name,
                'city'              => $faker->city,
                'zip_code'          => $faker->postCode,
                'phone'             => $user->phone,
                'province'          => $faker->state,
                'country'           => $faker->country,
            ])->assignRole('organizer');

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
