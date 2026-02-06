<?php

namespace Database\Seeders;

use App\Models\Table;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        $branch1 = Branch::first();
        
        if (!$branch1) {
            echo "No branch found. Run BranchSeeder first.\n";
            return;
        }

        $tables = [
            // Ground Floor
            ['table_number' => 'T1', 'name' => 'Table 1', 'floor' => 'Ground Floor', 'capacity' => 4, 'status' => 'available', 'sort_order' => 1],
            ['table_number' => 'T2', 'name' => 'Table 2', 'floor' => 'Ground Floor', 'capacity' => 4, 'status' => 'available', 'sort_order' => 2],
            ['table_number' => 'T3', 'name' => 'Table 3', 'floor' => 'Ground Floor', 'capacity' => 4, 'status' => 'available', 'sort_order' => 3],
            ['table_number' => 'T4', 'name' => 'Table 4', 'floor' => 'Ground Floor', 'capacity' => 6, 'status' => 'available', 'sort_order' => 4],
            ['table_number' => 'T5', 'name' => 'Table 5', 'floor' => 'Ground Floor', 'capacity' => 6, 'status' => 'available', 'sort_order' => 5],
            // First Floor
            ['table_number' => 'T6', 'name' => 'Table 6', 'floor' => 'First Floor', 'capacity' => 4, 'status' => 'available', 'sort_order' => 6],
            ['table_number' => 'T7', 'name' => 'Table 7', 'floor' => 'First Floor', 'capacity' => 4, 'status' => 'available', 'sort_order' => 7],
            ['table_number' => 'T8', 'name' => 'Table 8', 'floor' => 'First Floor', 'capacity' => 8, 'status' => 'available', 'sort_order' => 8],
        ];

        foreach ($tables as $tableData) {
            Table::updateOrCreate(
                ['table_number' => $tableData['table_number'], 'branch_id' => $branch1->id],
                array_merge($tableData, ['branch_id' => $branch1->id, 'is_active' => true])
            );
        }

        echo "Created " . count($tables) . " tables\n";
    }
}