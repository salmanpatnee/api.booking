<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Permission::create(['name' => 'dashboard-view']);

        Permission::create(['name' => 'user-view']);
        Permission::create(['name' => 'user-add']);
        Permission::create(['name' => 'user-edit']);
        Permission::create(['name' => 'user-delete']);

        Permission::create(['name' => 'role-view']);
        Permission::create(['name' => 'role-add']);
        Permission::create(['name' => 'role-edit']);
        Permission::create(['name' => 'role-delete']);

        Permission::create(['name' => 'supplier-view']);
        Permission::create(['name' => 'supplier-add']);
        Permission::create(['name' => 'supplier-edit']);
        Permission::create(['name' => 'supplier-delete']);

        Permission::create(['name' => 'customer-view']);
        Permission::create(['name' => 'customer-add']);
        Permission::create(['name' => 'customer-edit']);
        Permission::create(['name' => 'customer-delete']);

        Permission::create(['name' => 'category-view']);
        Permission::create(['name' => 'category-add']);
        Permission::create(['name' => 'category-edit']);
        Permission::create(['name' => 'category-delete']);

        Permission::create(['name' => 'brand-view']);
        Permission::create(['name' => 'brand-add']);
        Permission::create(['name' => 'brand-edit']);
        Permission::create(['name' => 'brand-delete']);

        Permission::create(['name' => 'product-view']);
        Permission::create(['name' => 'product-add']);
        Permission::create(['name' => 'product-edit']);
        Permission::create(['name' => 'product-delete']);

        Permission::create(['name' => 'location-view']);
        Permission::create(['name' => 'location-add']);
        Permission::create(['name' => 'location-edit']);
        Permission::create(['name' => 'location-delete']);

        Permission::create(['name' => 'purchase-view']);
        Permission::create(['name' => 'purchase-add']);
        Permission::create(['name' => 'purchase-edit']);
        Permission::create(['name' => 'purchase-delete']);

        Permission::create(['name' => 'stock-view']);
        Permission::create(['name' => 'stock-edit']);

        Permission::create(['name' => 'po-view']);
        Permission::create(['name' => 'po-add']);
        Permission::create(['name' => 'po-edit']);
        Permission::create(['name' => 'po-delete']);

        Permission::create(['name' => 'pos-manage']);

        Permission::create(['name' => 'sale-view']);
        Permission::create(['name' => 'sale-add']);
        Permission::create(['name' => 'sale-edit']);
        Permission::create(['name' => 'sale-delete']);

        Permission::create(['name' => 'sale-return-view']);
        Permission::create(['name' => 'sale-return-add']);
        Permission::create(['name' => 'sale-return-edit']);
        Permission::create(['name' => 'sale-return-delete']);

        Permission::create(['name' => 'purchase-return-view']);
        Permission::create(['name' => 'purchase-return-add']);
        Permission::create(['name' => 'purchase-return-edit']);
        Permission::create(['name' => 'purchase-return-delete']);

        Permission::create(['name' => 'discount-view']);
        Permission::create(['name' => 'discount-edit']);

        Permission::create(['name' => 'account-view']);
        // Permission::create(['name' => 'account-add']);
        // Permission::create(['name' => 'account-edit']);
        // Permission::create(['name' => 'account-delete']);

        Permission::create(['name' => 'expense-view']);
        Permission::create(['name' => 'expense-add']);
        Permission::create(['name' => 'expense-edit']);
        Permission::create(['name' => 'expense-delete']);

        Permission::create(['name' => 'report-view']);

        Permission::create(['name' => 'hrm-view']);
    }
}
