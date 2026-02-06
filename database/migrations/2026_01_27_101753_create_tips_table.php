<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('invoice_id')->constrained();
            $table->foreignId('payment_id')->constrained();
            $table->foreignId('waiter_id')->nullable()->constrained('users');
            $table->foreignId('cashier_id')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->enum('collection_status', ['pending', 'collected'])->default('pending');
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tips');
    }
};
