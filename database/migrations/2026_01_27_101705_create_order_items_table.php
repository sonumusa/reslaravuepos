<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->decimal('unit_price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->decimal('subtotal', 12, 2);
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'sent', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->boolean('is_void')->default(false);
            $table->string('void_reason')->nullable();
            $table->foreignId('void_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
