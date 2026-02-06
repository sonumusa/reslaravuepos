<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('terminal_id')->nullable()->constrained('pos_terminals');
            $table->foreignId('table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number', 50);
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->enum('status', ['draft', 'open', 'hold', 'sent_to_kitchen', 'preparing', 'ready', 'served', 'completed', 'paid', 'cancelled', 'void'])->default('draft');
            $table->integer('guest_count')->default(1);
            $table->text('notes')->nullable();
            $table->text('kitchen_notes')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->boolean('created_offline')->default(false);
            $table->timestamp('sent_to_kitchen_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
