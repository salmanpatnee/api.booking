<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Location::create([
            'name' => 'UK',
            'address' => '127C Bawtry Rd',
            'city' => 'Rotherham',
            'phone'=> '01709 542255'
        ]);
    }
}
