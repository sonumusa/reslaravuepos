<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', ['purchase', 'sale', 'adjustment', 'waste', 'transfer_in', 'transfer_out']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->decimal('previous_stock', 12, 3);
            $table->decimal('new_stock', 12, 3);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
