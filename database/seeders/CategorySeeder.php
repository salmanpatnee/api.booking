<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::create([
            'id' => 1,
            'name' => "Uncategorized",
        ]);

        // foreach (range(1, 300) as $i) {

        //     Category::create([
        //         'name' => 'Category ' . $i,
        //     ]);
        // }

        // $data = ['Tablets', 'Syrups'];
        // foreach ($data as $category) {
        //     Category::create([
        //         'name'=>$category
        //     ]);
        // }
    }
}
