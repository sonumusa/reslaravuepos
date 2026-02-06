<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Utilities', 'code' => 'UTIL', 'icon' => '💡'],
            ['name' => 'Rent', 'code' => 'RENT', 'icon' => '🏢'],
            ['name' => 'Salaries', 'code' => 'SAL', 'icon' => '💰'],
            ['name' => 'Supplies', 'code' => 'SUP', 'icon' => '📦'],
            ['name' => 'Marketing', 'code' => 'MKTG', 'icon' => '📢'],
            ['name' => 'Maintenance', 'code' => 'MAINT', 'icon' => '🔧'],
            ['name' => 'Transportation', 'code' => 'TRANS', 'icon' => '🚗'],
            ['name' => 'Other', 'code' => 'OTHER', 'icon' => '📝'],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
