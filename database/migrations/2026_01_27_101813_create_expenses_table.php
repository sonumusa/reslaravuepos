<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('expense_categories');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('expense_number', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'card', 'bank', 'other']);
            $table->string('receipt_image')->nullable();
            $table->date('expense_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
