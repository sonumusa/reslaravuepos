<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->text('address');
            $table->string('city', 100);
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('ntn_number', 20);
            $table->string('strn_number', 20)->nullable();
            $table->decimal('gst_rate', 5, 2)->default(16.00);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
