<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'HQ001')->first();

        $customers = [
            [
                'branch_id' => $branch1->id,
                'name' => 'Walk-in Customer',
                'phone' => null,
                'email' => null,
                'loyalty_points' => 0,
                'is_walkin' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Ahmed Ali',
                'phone' => '0300-1111111',
                'email' => 'ahmed@example.com',
                'address' => 'Block A, DHA Phase 5, Lahore',
                'birthday' => '1985-05-15',
                'loyalty_points' => 150,
                'total_spent' => 15000.00,
                'total_orders' => 12,
                'is_walkin' => false,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Sara Khan',
                'phone' => '0300-2222222',
                'email' => 'sara@example.com',
                'address' => 'Gulberg III, Lahore',
                'birthday' => '1990-08-22',
                'loyalty_points' => 250,
                'total_spent' => 25000.00,
                'total_orders' => 20,
                'is_walkin' => false,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Hassan Raza',
                'phone' => '0300-3333333',
                'email' => 'hassan@example.com',
                'address' => 'Johar Town, Lahore',
                'birthday' => '1988-12-10',
                'loyalty_points' => 80,
                'total_spent' => 8000.00,
                'total_orders' => 6,
                'is_walkin' => false,
            ],
        ];

        foreach ($customers as $customer) {
            if ($customer['is_walkin']) {
                Customer::updateOrCreate(
                    ['is_walkin' => true],
                    $customer
                );
            } else {
                Customer::updateOrCreate(
                    ['phone' => $customer['phone']],
                    $customer
                );
            }
        }
    }
}
