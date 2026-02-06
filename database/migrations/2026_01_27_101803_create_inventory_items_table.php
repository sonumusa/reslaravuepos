<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku', 50)->nullable();
            $table->string('unit', 20);
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('min_stock_level', 12, 3)->default(0);
            $table->decimal('max_stock_level', 12, 3)->nullable();
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->string('supplier')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
