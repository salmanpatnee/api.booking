<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@icrack.co.uk',
            'password' => 'I&hs&n1*63m9',
        ]);

        /*
        User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@pharmasquare.com',
            'password' => '12345678',
        ])->assignRole('Cashier');

        User::create([
            'name' => 'POS User',
            'email' => 'pos@pharmasquare.com',
            'password' => '12345678',
        ])->assignRole('POS User');
        */
    }
}
