<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (range(1, 300) as $i) {

            Brand::create([
                'name' => 'Brand ' . $i,
            ]);
        }

        // $brands = ['Woodwards', 'National', 'Nipro'];
        // foreach ($brands as $brand) {

        //     Brand::create([
        //         'name' => $brand
        //     ]);
        // }
    }
}
