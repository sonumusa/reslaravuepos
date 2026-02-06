<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            [
                'code' => 'HQ001',
                'name' => 'Main Branch - Gulberg',
                'address' => 'Plot 123, Main Boulevard, Gulberg III',
                'city' => 'Lahore',
                'phone' => '042-35714000',
                'email' => 'gulberg@restaurant.com',
                'ntn_number' => '1234567-8',
                'strn_number' => 'STRN-12345',
                'gst_rate' => 16.00,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Karachi',
                    'currency' => 'PKR',
                    'offline_mode_enabled' => true,
                    'auto_print_kitchen' => true,
                    'auto_print_receipt' => false,
                ],
            ],
            [
                'code' => 'BR002',
                'name' => 'DHA Branch',
                'address' => '456 Y Block, DHA Phase 5',
                'city' => 'Lahore',
                'phone' => '042-35714001',
                'email' => 'dha@restaurant.com',
                'ntn_number' => '1234567-8',
                'strn_number' => 'STRN-12346',
                'gst_rate' => 16.00,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Karachi',
                    'currency' => 'PKR',
                    'offline_mode_enabled' => true,
                ],
            ],
            [
                'code' => 'BR003',
                'name' => 'Johar Town Branch',
                'address' => '789 Block H, Johar Town',
                'city' => 'Lahore',
                'phone' => '042-35714002',
                'email' => 'johar@restaurant.com',
                'ntn_number' => '1234567-8',
                'strn_number' => 'STRN-12347',
                'gst_rate' => 16.00,
                'is_active' => true,
                'settings' => [
                    'timezone' => 'Asia/Karachi',
                    'currency' => 'PKR',
                ],
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
