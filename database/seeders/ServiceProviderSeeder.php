<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\ServicesCategory;

class ServiceProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            ['name' => 'Sound system rental', 'description' => '', 'card_photo' => ''],
            ['name' => 'Equipment rental', 'description' => '', 'card_photo' => ''],
            ['name' => 'Guitar technician', 'description' => '', 'card_photo' => ''],
            ['name' => 'Instrument tutorials', 'description' => '', 'card_photo' => ''],
            ['name' => 'Video and Photo service provider', 'description' => '', 'card_photo' => ''],
            ['name' => 'Video Editors', 'description' => '', 'card_photo' => ''],
        ];

        foreach ($categories as $category) {
            ServicesCategory::create($category);
        }
    }
}
