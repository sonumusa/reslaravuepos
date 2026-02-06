<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'HQ001')->first();

        $discounts = [
            [
                'branch_id' => null,
                'code' => 'WELCOME10',
                'name' => 'Welcome Discount',
                'description' => '10% off for new customers',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_amount' => 500.00,
                'max_discount_amount' => 500.00,
                'usage_limit' => 100,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'is_active' => true,
            ],
            [
                'branch_id' => null,
                'code' => 'FLAT200',
                'name' => 'Flat 200 Off',
                'description' => 'Flat Rs.200 off on orders above 2000',
                'type' => 'fixed',
                'value' => 200.00,
                'min_order_amount' => 2000.00,
                'max_discount_amount' => null,
                'usage_limit' => 50,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addMonth(),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'code' => 'LOYALTY15',
                'name' => 'Loyalty Discount',
                'description' => '15% off for loyal customers',
                'type' => 'percentage',
                'value' => 15.00,
                'min_order_amount' => 1000.00,
                'max_discount_amount' => 1000.00,
                'usage_limit' => null,
                'used_count' => 0,
                'valid_from' => now(),
                'valid_until' => null,
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $discount) {
            Discount::updateOrCreate(
                ['code' => $discount['code']],
                $discount
            );
        }
    }
}
