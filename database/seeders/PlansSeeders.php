<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanInclusion;

class PlansSeeders extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $plans = [
            [
                "plans" => ['name' => 'Customer', 'cost_text' => 'FREE', 'cost_value' => 0.00, 'description' => '', 'is_active' => true, 'plan_type' => "monthly",],
                "inclusions" => ["Create a Song with Artist", "Look For an Artist",],
            ],
            [
                "plans" => ['name' => 'Artist', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => '', 'plan_type' => '', 'is_active' => true, 'plan_type' => "monthly",],
                "inclusions" => ["Accept Booking", "Create a song for client", "Apply for Events", "Look for Services"],
            ],
            [
                "plans" => ['name' => 'Organizer', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => '', 'plan_type' => '', 'is_active' => true, 'plan_type' => "monthly",],
                "inclusions" => ["Create Event", "Look for Artist", "Look for Services",],
            ],
        ];

        foreach ($plans as $key) {

            $data = Plan::create($key['plans']);
            foreach ($key['inclusions'] as $inclusion) {
                $data->inclusions()->create(['inclusions' => $inclusion]);
            }
        }
    }
}
