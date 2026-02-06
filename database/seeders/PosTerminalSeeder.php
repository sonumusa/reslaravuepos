<?php

namespace Database\Seeders;

use App\Models\PosTerminal;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class PosTerminalSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::where('code', 'HQ001')->first();
        $branch2 = Branch::where('code', 'BR002')->first();

        $terminals = [
            // Gulberg Branch
            [
                'branch_id' => $branch1->id,
                'terminal_code' => 'CASH-01',
                'name' => 'Cashier Terminal 1',
                'device_id' => 'DEVICE-001',
                'type' => 'cashier',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'terminal_code' => 'CASH-02',
                'name' => 'Cashier Terminal 2',
                'device_id' => 'DEVICE-002',
                'type' => 'cashier',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'terminal_code' => 'WAIT-01',
                'name' => 'Waiter Tablet 1',
                'device_id' => 'TABLET-001',
                'type' => 'waiter',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'terminal_code' => 'WAIT-02',
                'name' => 'Waiter Tablet 2',
                'device_id' => 'TABLET-002',
                'type' => 'waiter',
                'is_active' => true,
            ],
            [
                'branch_id' => $branch1->id,
                'terminal_code' => 'KDS-01',
                'name' => 'Kitchen Display 1',
                'device_id' => 'KDS-001',
                'type' => 'kds',
                'is_active' => true,
            ],
            // DHA Branch
            [
                'branch_id' => $branch2->id,
                'terminal_code' => 'DHA-CASH-01',
                'name' => 'DHA Cashier 1',
                'device_id' => 'DEVICE-003',
                'type' => 'cashier',
                'is_active' => true,
            ],
        ];

        foreach ($terminals as $terminal) {
            PosTerminal::updateOrCreate(
                ['terminal_code' => $terminal['terminal_code']],
                $terminal
            );
        }
    }
}
