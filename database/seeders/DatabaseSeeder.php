<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(AccountHeadSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        // $this->call(BankAccountSeeder::class);
        $this->call(LocationSeeder::class);
        // $this->call(PermissionSeeder::class);
        // $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(EmployeeSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(CustomerSeeder::class);
        $this->call(CategorySeeder::class);
        // $this->call(ExpenseTypeSeeder::class);
        // $this->call(JournalEntrySeeder::class);
        // $this->call(BrandSeeder::class);
        // \App\Models\Product::factory(10)->create();
        $this->call(ProductSeeder::class);
        // $this->call(AccountSeeder::class);
        // $this->call(PurchaseSeeder::class);
        // $this->call(SaleSeeder::class);
    }
}
