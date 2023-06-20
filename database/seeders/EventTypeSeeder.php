<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EventType;
use Faker\Factory as Faker;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        //$faker = Faker::create();
        EventType::create([
            'name' => 'Battle of the bands',
        ]);

        EventType::create([
            'name' => 'Weddings',
        ]);

        EventType::create([
            'name' => 'Concerts',
        ]);

        EventType::create([
            'name' => 'Bar Events',
        ]);

        EventType::create([
            'name' => 'Exhibitions',
        ]);

        EventType::create([
            'name' => 'Birthdays',
        ]);

        EventType::create([
            'name' => 'Debut',
        ]);

        EventType::create([
            'name' => 'Charity Event',
        ]);

        EventType::create([
            'name' => 'Festivals',
        ]);
    }
}
