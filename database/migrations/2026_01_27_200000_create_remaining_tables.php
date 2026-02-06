<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 4: sessions
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Table 12: menu_item_modifiers
        if (!Schema::hasTable('menu_item_modifiers')) {
            Schema::create('menu_item_modifiers', function (Blueprint $table) {
                $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
                $table->foreignId('menu_modifier_id')->constrained('menu_modifiers')->cascadeOnDelete();
                $table->primary(['menu_item_id', 'menu_modifier_id']);
            });
        }

        // Table 15: order_item_modifiers
        if (!Schema::hasTable('order_item_modifiers')) {
            Schema::create('order_item_modifiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
                $table->foreignId('menu_modifier_id')->nullable()->constrained('menu_modifiers')->nullOnDelete();
                $table->string('modifier_name');
                $table->string('group_name');
                $table->decimal('price', 10, 2)->default(0);
                $table->timestamps();
            });
        }

        // Table 3: password_reset_tokens
        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_modifiers');
        Schema::dropIfExists('menu_item_modifiers');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
    }
};
