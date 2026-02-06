<?php

namespace Database\Seeders; 

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'HQ001')->first();
        $branch2 = Branch::where('code', 'BR002')->first();

        $users = [
            [
                'branch_id' => null,
                'name' => 'Super Admin',
                'email' => 'admin@reslaravuepos.com',
                'phone' => '0300-1234567',
                'pin' => '0000',
                'role' => 'superadmin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Branch Admin',
                'email' => 'admin@gulberg.com',
                'phone' => '0300-1234568',
                'pin' => '1111',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Cashier One',
                'email' => 'cashier1@gulberg.com',
                'phone' => '0300-1234569',
                'pin' => '1234',
                'role' => 'cashier',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Cashier Two',
                'email' => 'cashier2@gulberg.com',
                'phone' => '0300-1234570',
                'pin' => '2345',
                'role' => 'cashier',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Waiter One',
                'email' => 'waiter1@gulberg.com',
                'phone' => '0300-1234571',
                'pin' => '3456',
                'role' => 'waiter',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Waiter Two',
                'email' => 'waiter2@gulberg.com',
                'phone' => '0300-1234572',
                'pin' => '4567',
                'role' => 'waiter',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'name' => 'Kitchen Staff',
                'email' => 'kitchen@gulberg.com',
                'phone' => '0300-1234573',
                'pin' => '5678',
                'role' => 'kitchen',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'branch_id' => $branch2->id,
                'name' => 'DHA Cashier',
                'email' => 'cashier@dha.com',
                'phone' => '0300-1234574',
                'pin' => '6789',
                'role' => 'cashier',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
