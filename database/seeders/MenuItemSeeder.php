<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Category;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $appetizers = Category::where('slug', 'appetizers')->first();
        $mainCourse = Category::where('slug', 'main-course')->first();
        $bbq = Category::where('slug', 'bbq-grills')->first();
        $burgers = Category::where('slug', 'burgers-sandwiches')->first();
        $pizza = Category::where('slug', 'pizza-pasta')->first();
        $desserts = Category::where('slug', 'desserts')->first();
        $beverages = Category::where('slug', 'beverages')->first();
        $hotDrinks = Category::where('slug', 'hot-drinks')->first();

        $menuItems = [
            // Appetizers
            ['category_id' => $appetizers->id, 'name' => 'French Fries', 'short_name' => 'Fries', 'price' => 250.00, 'prep_time_minutes' => 8],
            ['category_id' => $appetizers->id, 'name' => 'Chicken Wings (6pcs)', 'short_name' => 'Wings', 'price' => 450.00, 'prep_time_minutes' => 12],
            ['category_id' => $appetizers->id, 'name' => 'Spring Rolls (4pcs)', 'short_name' => 'S.Rolls', 'price' => 350.00, 'prep_time_minutes' => 10],
            ['category_id' => $appetizers->id, 'name' => 'Onion Rings', 'short_name' => 'O.Rings', 'price' => 280.00, 'prep_time_minutes' => 8],

            // Main Course
            ['category_id' => $mainCourse->id, 'name' => 'Chicken Biryani', 'short_name' => 'Ch.Biryani', 'price' => 550.00, 'prep_time_minutes' => 20],
            ['category_id' => $mainCourse->id, 'name' => 'Mutton Biryani', 'short_name' => 'Mt.Biryani', 'price' => 750.00, 'prep_time_minutes' => 25],
            ['category_id' => $mainCourse->id, 'name' => 'Chicken Karahi (Half)', 'short_name' => 'Ch.Karahi', 'price' => 900.00, 'prep_time_minutes' => 18],
            ['category_id' => $mainCourse->id, 'name' => 'Mutton Karahi (Half)', 'short_name' => 'Mt.Karahi', 'price' => 1200.00, 'prep_time_minutes' => 22],

            // BBQ & Grills
            ['category_id' => $bbq->id, 'name' => 'Chicken Tikka', 'short_name' => 'Ch.Tikka', 'price' => 650.00, 'prep_time_minutes' => 15],
            ['category_id' => $bbq->id, 'name' => 'Seekh Kabab (6pcs)', 'short_name' => 'S.Kabab', 'price' => 550.00, 'prep_time_minutes' => 12],
            ['category_id' => $bbq->id, 'name' => 'Malai Boti', 'short_name' => 'M.Boti', 'price' => 700.00, 'prep_time_minutes' => 15],
            ['category_id' => $bbq->id, 'name' => 'BBQ Platter', 'short_name' => 'BBQ Platter', 'price' => 1500.00, 'prep_time_minutes' => 20],

            // Burgers & Sandwiches
            ['category_id' => $burgers->id, 'name' => 'Beef Burger', 'short_name' => 'Beef B', 'price' => 450.00, 'prep_time_minutes' => 10],
            ['category_id' => $burgers->id, 'name' => 'Chicken Burger', 'short_name' => 'Ch B', 'price' => 400.00, 'prep_time_minutes' => 10],
            ['category_id' => $burgers->id, 'name' => 'Zinger Burger', 'short_name' => 'Zinger', 'price' => 500.00, 'prep_time_minutes' => 12],
            ['category_id' => $burgers->id, 'name' => 'Club Sandwich', 'short_name' => 'Club SW', 'price' => 550.00, 'prep_time_minutes' => 10],

            // Pizza & Pasta
            ['category_id' => $pizza->id, 'name' => 'Margherita Pizza (M)', 'short_name' => 'Marg Pizza', 'price' => 850.00, 'prep_time_minutes' => 15],
            ['category_id' => $pizza->id, 'name' => 'Chicken Fajita Pizza (M)', 'short_name' => 'Faj Pizza', 'price' => 1100.00, 'prep_time_minutes' => 15],
            ['category_id' => $pizza->id, 'name' => 'Chicken Alfredo Pasta', 'short_name' => 'Alfredo', 'price' => 650.00, 'prep_time_minutes' => 12],
            ['category_id' => $pizza->id, 'name' => 'Arrabiata Pasta', 'short_name' => 'Arrabiata', 'price' => 600.00, 'prep_time_minutes' => 12],

            // Desserts
            ['category_id' => $desserts->id, 'name' => 'Chocolate Brownie', 'short_name' => 'Brownie', 'price' => 350.00, 'prep_time_minutes' => 5],
            ['category_id' => $desserts->id, 'name' => 'Ice Cream Sundae', 'short_name' => 'Sundae', 'price' => 300.00, 'prep_time_minutes' => 5],
            ['category_id' => $desserts->id, 'name' => 'Gulab Jamun (2pcs)', 'short_name' => 'G.Jamun', 'price' => 200.00, 'prep_time_minutes' => 3],
            ['category_id' => $desserts->id, 'name' => 'Ras Malai (2pcs)', 'short_name' => 'R.Malai', 'price' => 250.00, 'prep_time_minutes' => 3],

            // Beverages
            ['category_id' => $beverages->id, 'name' => 'Coca Cola', 'short_name' => 'Coke', 'price' => 120.00, 'prep_time_minutes' => 2],
            ['category_id' => $beverages->id, 'name' => 'Sprite', 'short_name' => 'Sprite', 'price' => 120.00, 'prep_time_minutes' => 2],
            ['category_id' => $beverages->id, 'name' => 'Fresh Lime', 'short_name' => 'F.Lime', 'price' => 180.00, 'prep_time_minutes' => 5],
            ['category_id' => $beverages->id, 'name' => 'Mango Juice', 'short_name' => 'Mango J', 'price' => 200.00, 'prep_time_minutes' => 5],

            // Hot Drinks
            ['category_id' => $hotDrinks->id, 'name' => 'Espresso', 'short_name' => 'Espresso', 'price' => 180.00, 'prep_time_minutes' => 5],
            ['category_id' => $hotDrinks->id, 'name' => 'Cappuccino', 'short_name' => 'Cappucc', 'price' => 250.00, 'prep_time_minutes' => 6],
            ['category_id' => $hotDrinks->id, 'name' => 'Latte', 'short_name' => 'Latte', 'price' => 280.00, 'prep_time_minutes' => 6],
            ['category_id' => $hotDrinks->id, 'name' => 'Masala Tea', 'short_name' => 'M.Tea', 'price' => 100.00, 'prep_time_minutes' => 5],
        ];

        foreach ($menuItems as $item) {
            MenuItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'branch_id' => null,
                    'category_id' => $item['category_id'],
                    'short_name' => $item['short_name'],
                    'description' => 'Delicious ' . $item['name'],
                    'price' => $item['price'],
                    'cost_price' => $item['price'] * 0.40, // 40% cost
                    'sku' => strtoupper(substr($item['short_name'], 0, 3)) . '-' . substr(md5($item['name']), 0, 3), // Deterministic SKU
                    'tax_type' => 'inclusive',
                    'tax_rate' => 16.00,
                    'is_available' => true,
                    'is_featured' => rand(0, 1) ? true : false,
                    'track_inventory' => false,
                    'prep_time_minutes' => $item['prep_time_minutes'],
                    'sort_order' => 0,
                ]
            );
        }
    }
}
