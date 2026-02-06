<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_number', 50);
            $table->enum('method', ['cash', 'card', 'mobile', 'split', 'credit', 'other']);
            $table->string('card_type')->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('transaction_reference')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('tip', 12, 2)->default(0);
            $table->decimal('tendered', 12, 2)->nullable();
            $table->decimal('change', 12, 2)->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->text('notes')->nullable();
            $table->boolean('created_offline')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
