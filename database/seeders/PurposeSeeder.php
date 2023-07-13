<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Purpose;

class PurposeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Purpose::create(['name' => 'Birthday',]);
        Purpose::create(['name' => 'Anniversary',]);
        Purpose::create(['name' => 'Funeral',]);
        Purpose::create(['name' => 'Wedding',]);
        Purpose::create(['name' => 'Proposal',]);
        Purpose::create(['name' => 'Other',]);
    }
}
