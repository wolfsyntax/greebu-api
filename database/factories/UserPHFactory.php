<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPH>
 */
class UserPHFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        fake()->addProvider(new \Faker\Provider\en_PH\PhoneNumber(fake()));
        // $faker->addProvider(new \Faker\Provider\en_PH\Address(fake()));

        return [
            'first_name' => fake()->unique()->firstName(),
            'last_name' => fake()->unique()->lastName(),
            'email' => fake()->unique()->freeEmail(),
            'email_verified_at' => now(),
            'password' => 'password', // password
            'phone' => '+639' . fake()->unique()->numerify('#########'),
            'remember_token' => Str::random(10),
            'username' => fake()->userName(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
