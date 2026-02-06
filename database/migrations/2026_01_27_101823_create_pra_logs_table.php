<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pra_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->cascadeOnDelete();
            $table->enum('action', ['submit', 'verify', 'cancel', 'test']);
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            
            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pra_logs');
    }
};
