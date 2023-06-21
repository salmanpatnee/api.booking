<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            'company_name' => 'iCrack', 
            'address' => '84 Gracechurch Shopping Centre, The Parade, Birmingham, Sutton Coldfield B72 1PH, United Kingdom', 
            'phone' => '07883 731494', 
            'review_link' => 'https://g.page/r/Cd5T4cka7ogJEBM/review', 
        ];

        foreach($settings as $name => $value){
            Setting::create([
                'name' => $name, 
                'value' => $value
            ]);
        }
    }
}
