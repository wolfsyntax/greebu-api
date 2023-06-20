<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SupportedLanguage;

class SupportedLanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        SupportedLanguage::create(['name' => 'English',]);
        SupportedLanguage::create(['name' => 'Tagalog',]);
        SupportedLanguage::create(['name' => 'Bikol',]);
    }
}
