<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'HQ001')->first();

        $categories = [
            [
                'branch_id' => null, // Global category
                'name' => 'Appetizers',
                'slug' => 'appetizers',
                'icon' => '🍟',
                'color' => '#F59E0B',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Main Course',
                'slug' => 'main-course',
                'icon' => '🍽️',
                'color' => '#EF4444',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'BBQ & Grills',
                'slug' => 'bbq-grills',
                'icon' => '🍖',
                'color' => '#DC2626',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Burgers & Sandwiches',
                'slug' => 'burgers-sandwiches',
                'icon' => '🍔',
                'color' => '#F97316',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Pizza & Pasta',
                'slug' => 'pizza-pasta',
                'icon' => '🍕',
                'color' => '#EAB308',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Desserts',
                'slug' => 'desserts',
                'icon' => '🍰',
                'color' => '#EC4899',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Beverages',
                'slug' => 'beverages',
                'icon' => '🥤',
                'color' => '#3B82F6',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'name' => 'Hot Drinks',
                'slug' => 'hot-drinks',
                'icon' => '☕',
                'color' => '#92400E',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
