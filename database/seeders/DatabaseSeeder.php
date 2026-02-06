<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            PosTerminalSeeder::class,
            TableSeeder::class,
            CategorySeeder::class,
            MenuItemSeeder::class,
            MenuModifierSeeder::class,
            CustomerSeeder::class,
            ExpenseCategorySeeder::class,
            DiscountSeeder::class,
        ]);
    }
}
