<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('terminal_id')->constrained('pos_terminals');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->decimal('cash_difference', 12, 2)->nullable();
            $table->decimal('total_sales', 12, 2)->default(0);
            $table->decimal('total_cash_sales', 12, 2)->default(0);
            $table->decimal('total_card_sales', 12, 2)->default(0);
            $table->decimal('total_tips', 12, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('total_refunds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed', 'suspended'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
