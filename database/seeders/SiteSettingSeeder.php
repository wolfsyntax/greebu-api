<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        SiteSetting::create([
            'meta_key' => 'cancellation_reasons',
            'meta_key_label' => 'Cancellation Reason',
            'is_dropdown' => false,
            'is_active' => true,
            'meta_value' => '[{"name": "Need to change the event address / location"}, {"name": "Natural disasters or severe weather conditions that could make the event impossible or unsafe to attend"}, {"name": "Health and safety concerns related to a pandemic or other outbreak"}, {"name": "Venue issues, such as double booking, unexpected closures or renovations, or technical problems"}, {"name": "Unexpected illness, injury or death of a key participant or organizer"}, {"name": "Found cheaper elsewhere"}, {"name": "Don\'t want to book anymore"}, {"name": "Others"}]',
        ]);
        /*
        [{"name":"AFFIN BANK BHD","code":"PABB","payment_mode":"IBG"},{"name":"AL RAJHI BANK","code":"ARB","payment_mode":"IBG"},{"name":"ALLIANCE BANK MALAYSIA BHD","code":"ALBB","payment_mode":"IBG"},{"name":"AMBANK BERHAD","code":"AMBB","payment_mode":"IBG"},{"name":"BANK ISLAM BHD","code":"BIMB","payment_mode":"IBG"},{"name":"BANK KERJASAMA RAKYAT MALAYSIA","code":"BKRM","payment_mode":"IBG"},{"name":"BANK MUAMALAT MALAYSIA BERHAD","code":"BMMB","payment_mode":"IBG"},{"name":"BANK OF AMERICA BHD","code":"BOFA","payment_mode":"IBG"},{"name":"BANK OF CHINA (M) BHD","code":"BOCM","payment_mode":"IBG"},{"name":"MUFG Bank (M) BERHAD","code":"BTMU","payment_mode":"IBG"},{"name":"BANK PERTANIAN MALAYSIA BHD","code":"AGRO","payment_mode":"IBG"},{"name":"BANK SIMPANAN NASIONAL","code":"BSNB","payment_mode":"IBG"},{"name":"BNP PARIBAS MALAYSIA BERHAD","code":"BNPM","payment_mode":"IBG"},{"name":"CIMB BANK BHD","code":"CIMB","payment_mode":"IBG"},{"name":"CITIBANK BHD","code":"CITI","payment_mode":"IBG"},{"name":"DEUTSCHE BANK (M) BHD","code":"DEUM","payment_mode":"IBG"},{"name":"HONGKONG BANK MALAYSIA BHD","code":"HSBC","payment_mode":"IBG"},{"name":"HONG LEONG BANK","code":"HLBB","payment_mode":"FT"},{"name":"IND&COMM BANK OF CHINA (M) BHD","code":"ICBC","payment_mode":"IBG"},{"name":"J.P. MORGAN CHASE BANK BHD","code":"JPMC","payment_mode":"IBG"},{"name":"KUWAIT FINANCE HOUSE","code":"KFHB","payment_mode":"IBG"},{"name":"MALAYAN BANKING BHD","code":"MBBB","payment_mode":"IBG"},{"name":"MIZUHO BANK (M) BERHAD","code":"MHCB","payment_mode":"IBG"},{"name":"OCBC BANK (M) BHD","code":"OCBC","payment_mode":"IBG"},{"name":"PUBLIC BANK BHD","code":"PBBB","payment_mode":"IBG"},{"name":"RHB BANK BHD","code":"RHBB","payment_mode":"IBG"},{"name":"STANDARD CHARTERED BANK BHD","code":"SCBB","payment_mode":"IBG"},{"name":"SUMITOMO MITSUI BANK BHD","code":"SMBC","payment_mode":"IBG"},{"name":"UNITED OVERSEAS BANK M BHD","code":"UOBB","payment_mode":"IBG"}]
        */
    }
}
