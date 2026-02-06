<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('terminal_code', 20)->unique();
            $table->string('name');
            $table->string('device_id')->nullable();
            $table->enum('type', ['cashier', 'waiter', 'kds']);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_terminals');
    }
};
