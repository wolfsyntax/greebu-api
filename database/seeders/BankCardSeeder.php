<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $banks = [
            ['name' => 'Asia United Bank', 'short_name' => 'AUB'],
            ['name' => 'Banco de Oro Bank', 'short_name' => 'BDO'],
            ['name' => 'Bank of America', 'short_name' => ''],
            ['name' => 'Bank of Commerce', 'short_name' => ''],
            ['name' => 'Bank of the Philippine Islands', 'short_name' => 'BPI'],
            ['name' => 'CTBC Bank', 'short_name' => ''],
            ['name' => 'China Banking Corporation', 'short_name' => 'Chinabank'],
            ['name' => 'Chinabank Savings', 'short_name' => ''],
            ['name' => 'Citibank', 'short_name' => ''],
            ['name' => 'Deutsche Bank', 'short_name' => ''],
            ['name' => 'Development Bank of the Philippines', 'short_name' => 'DBP'],
            ['name' => 'East West Bank', 'short_name' => ''],
            ['name' => 'Hongkong and Shanghai Banking Corporation', 'short_name' => 'HSBC'],
            ['name' => 'ICBC Manila', 'short_name' => ''],
            ['name' => 'Land Bank of the Philippines', 'short_name' => 'Landbank'],
            ['name' => 'Maybank Philippines', 'short_name' => ''],
            ['name' => 'Metropolitan Bank and Trust Company', 'short_name' => 'Metrobank'],
            ['name' => 'Philippine Bank of Communications', 'short_name' => 'PBCom'],
            ['name' => 'Philippine National Bank', 'short_name' => 'PNB'],
            ['name' => 'Philippine Savings Bank', 'short_name' => 'PSBank'],
            ['name' => 'Philippine Trust Company', 'short_name' => 'Philtrust Bank'],
            ['name' => 'Philippine Veterans Bank', 'short_name' => 'Veterans Bank'],
            ['name' => 'Rizal Commercial Banking Corporation', 'short_name' => 'RCBC'],
            ['name' => 'Robinsons Bank Corporation', 'short_name' => 'Robinsons Bank'],
            ['name' => 'SeaBank Philippines, Inc.', 'short_name' => ''],
            ['name' => 'Security Bank Corporation', 'short_name' => 'Security Bank'],
            ['name' => 'Standard Chartered Bank', 'short_name' => ''],
            ['name' => 'Sterling Bank', 'short_name' => ''],
            ['name' => 'Union Bank of the Philippines', 'short_name' => 'Unionbank'],
            ['name' => 'United Coconut Planters Bank', 'short_name' => 'UCPB'],
        ];

        foreach ($banks as $bank) {
            # code...
            Bank::create($bank);
        }
    }
}
