<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CancellationReason;

class CancellationReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        CancellationReason::create([
            'name' => "Need to change the event address / location.",
        ]);

        CancellationReason::create([
            'name' => "Natural disasters or severe weather conditions that could make the event impossible or unsafe to attend."
        ]);

        CancellationReason::create([
            'name' => "Health and safety concerns related to a pandemic or other outbreak.",
        ]);

        CancellationReason::create([
            'name' => "Venue issues, such as double booking, unexpected closures or renovations, or technical problems.",
        ]);

        CancellationReason::create([
            'name' => "Unexpected illness, injury or death of a key participant or organizer.",
        ]);

        CancellationReason::create([
            'name' => "Found cheaper elsewhere.",
        ]);

        CancellationReason::create([
            'name' => "Don't want to book anymore.",
        ]);

        CancellationReason::create([
            'name' => "Others.",
        ]);
    }
}
