<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
    }
}
