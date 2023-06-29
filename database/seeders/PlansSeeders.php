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
                "plans" => ['name' => 'FREE', 'cost_text' => 'FREE', 'cost_value' => 0.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'customers',],
                "inclusions" => ["Create a Song with Artist", "Look For an Artist", "Can view events"],
            ],
            [
                "plans" => ['name' => 'Monthly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'artists',],
                "inclusions" => ["Accept Booking", "Create a song for client", "Apply for Events", "Look for Services"],
            ],
            [
                "plans" => ['name' => 'Yearly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'artists',],
                "inclusions" => ["Accept Booking", "Create a song for client", "Apply for Events", "Look for Services"],
            ],
            [
                "plans" => ['name' => 'Monthly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'organizer',],
                "inclusions" => ["Accept Bookings", "Create a song for clients", "Apply for Events", "Look for Services"],
            ],
            [
                "plans" => ['name' => 'Yearly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'organizer',],
                "inclusions" => ["Create Event", "Look for Artist", "Look for Services",],
            ],
            [
                "plans" => ['name' => 'Monthly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'service-provider',],
                "inclusions" => ["Accept Bookings", "Create a song for clients", "Apply for Events", "Look for Services"],
            ],
            [
                "plans" => ['name' => 'Yearly', 'cost_text' => '2,000.00', 'cost_value' => 2000.00, 'description' => 'Lorem ipsum dolor sit consectetur.', 'is_active' => true, 'plan_type' => "monthly", 'account_type' => 'service-provider',],
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
