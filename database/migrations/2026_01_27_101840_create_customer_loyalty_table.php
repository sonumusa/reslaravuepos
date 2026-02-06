<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_loyalty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->enum('type', ['earn', 'redeem', 'expire', 'adjust']);
            $table->integer('points');
            $table->decimal('order_amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->integer('balance_after');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty');
    }
};
