<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PaymentMethodType;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $payment_methods_types = [
            'atome',
            'billease',
            'card',
            'dob',
            'dob_ubp',
            'gcash',
            'grab_pay',
            'paymaya'
        ];

        foreach ($payment_methods_types as $key => $value) {
            PaymentMethodType::create([
                'provider_name' => $value,
            ]);
        }
    }
}
