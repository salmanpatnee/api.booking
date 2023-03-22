<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $super_admin = Role::create(['name' => 'Super Admin']);
        $manager = Role::create(['name' => 'Manager']);
        $cashier = Role::create(['name' => 'Cashier']);
        $pos_user = Role::create(['name' => 'POS User']);

        $permissions = Permission::all();
        $super_admin->syncPermissions($permissions);

        $manager->syncPermissions($permissions);

        $cashier->syncPermissions(['customer-view', 'customer-add', 'customer-edit', 'sale-view', 'sale-add', 'sale-edit', 'sale-return-view', 'sale-return-add', 'sale-return-edit', 'expense-view', 'expense-add', 'expense-edit']);

        $pos_user->syncPermissions(['customer-view', 'customer-add', 'pos-manage']);
    }
}
