<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('local_invoice_number');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->string('discount_reason')->nullable();
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('tax_rate', 5, 2);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('tip_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('change_amount', 12, 2)->default(0);
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'void', 'refunded'])->default('draft');
            $table->enum('pra_status', ['not_required', 'pending', 'queued', 'submitted', 'failed', 'success'])->default('pending');
            $table->string('pra_invoice_number', 100)->nullable();
            $table->string('pra_fiscal_code', 100)->nullable();
            $table->text('pra_qr_code')->nullable();
            $table->timestamp('pra_submitted_at')->nullable();
            $table->text('pra_response')->nullable();
            $table->integer('pra_retry_count')->default(0);
            $table->boolean('created_offline')->default(false);
            $table->boolean('synced')->default(true);
            $table->timestamp('synced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
