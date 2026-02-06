<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kds_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained('pos_terminals');
            $table->enum('status', ['pending', 'acknowledged', 'preparing', 'ready', 'served']);
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kds_orders');
    }
};
