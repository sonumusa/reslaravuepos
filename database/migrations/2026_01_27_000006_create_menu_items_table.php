<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('sku')->nullable();
            $table->string('tax_type')->default('inclusive');
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('track_inventory')->default(false);
            $table->integer('prep_time_minutes')->default(0);
            $table->integer('sort_order')->default(0);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
