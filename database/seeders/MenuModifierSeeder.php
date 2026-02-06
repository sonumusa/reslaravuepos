<?php

namespace Database\Seeders;

use App\Models\MenuModifier;
use Illuminate\Database\Seeder;

class MenuModifierSeeder extends Seeder
{
    public function run(): void
    {
        $modifiers = [
            // Size modifiers
            ['group_name' => 'Size', 'name' => 'Regular', 'price' => 0.00, 'is_default' => true],
            ['group_name' => 'Size', 'name' => 'Large', 'price' => 100.00, 'is_default' => false],
            ['group_name' => 'Size', 'name' => 'Extra Large', 'price' => 200.00, 'is_default' => false],

            // Spice level
            ['group_name' => 'Spice Level', 'name' => 'Mild', 'price' => 0.00, 'is_default' => true],
            ['group_name' => 'Spice Level', 'name' => 'Medium', 'price' => 0.00, 'is_default' => false],
            ['group_name' => 'Spice Level', 'name' => 'Hot', 'price' => 0.00, 'is_default' => false],
            ['group_name' => 'Spice Level', 'name' => 'Extra Hot', 'price' => 0.00, 'is_default' => false],

            // Add-ons
            ['group_name' => 'Add-ons', 'name' => 'Extra Cheese', 'price' => 80.00, 'is_default' => false],
            ['group_name' => 'Add-ons', 'name' => 'Extra Sauce', 'price' => 50.00, 'is_default' => false],
            ['group_name' => 'Add-ons', 'name' => 'Bacon', 'price' => 120.00, 'is_default' => false],
            ['group_name' => 'Add-ons', 'name' => 'Fried Egg', 'price' => 70.00, 'is_default' => false],

            // Toppings
            ['group_name' => 'Toppings', 'name' => 'Olives', 'price' => 60.00, 'is_default' => false],
            ['group_name' => 'Toppings', 'name' => 'Jalapenos', 'price' => 60.00, 'is_default' => false],
            ['group_name' => 'Toppings', 'name' => 'Mushrooms', 'price' => 80.00, 'is_default' => false],

            // Drink options
            ['group_name' => 'Drink Type', 'name' => 'Regular', 'price' => 0.00, 'is_default' => true],
            ['group_name' => 'Drink Type', 'name' => 'Diet', 'price' => 0.00, 'is_default' => false],
            ['group_name' => 'Drink Type', 'name' => 'Zero', 'price' => 0.00, 'is_default' => false],
        ];

        foreach ($modifiers as $modifier) {
            MenuModifier::updateOrCreate(
                [
                    'group_name' => $modifier['group_name'],
                    'name' => $modifier['name']
                ],
                [
                    'branch_id' => null,
                    'price' => $modifier['price'],
                    'is_default' => $modifier['is_default'],
                    'is_active' => true,
                ]
            );
        }
    }
}
