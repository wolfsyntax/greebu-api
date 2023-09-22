<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\StaffRole;

class StaffRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $roles = [
            ['name' => 'Event Manager/Director', 'usage' => 'organizer',],
            ['name' => 'Event Coordinator', 'usage' => 'organizer',],
            ['name' => 'Marketing and Promotion Manager', 'usage' => 'organizer',],
            ['name' => 'Sponsorship/Partnership Manager', 'usage' => 'organizer',],
            ['name' => 'Program Manager', 'usage' => 'organizer',],
            ['name' => 'Production Manager', 'usage' => 'organizer',],
            ['name' => 'Operations Manager', 'usage' => 'organizer',],
            ['name' => 'Registration Manager', 'usage' => 'organizer',],
            // ['name' => 'Others', 'usage' => 'organizer',],
            // ['name' => '', 'usage' => 'organizer', ],
            // ['name' => '', 'usage' => 'artists', ],
            ['name' => 'Vocalist', 'usage' => 'artists',],
            ['name' => 'Drummer', 'usage' => 'artists',],
            ['name' => 'Lead Guitarist', 'usage' => 'artists',],
            ['name' => 'Rhythm Guitarist', 'usage' => 'artists',],
            ['name' => 'Bassist', 'usage' => 'artists',],
            ['name' => 'Keyboardist', 'usage' => 'artists',],
            // ['name' => 'Others', 'usage' => 'artists',],
            // ['name' => '', 'usage' => '', ],
        ];

        foreach ($roles as $role) {
            StaffRole::create($role);
        }
    }
}
