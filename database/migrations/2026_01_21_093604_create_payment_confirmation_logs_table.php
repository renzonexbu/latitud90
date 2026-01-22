<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_confirmation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('cascade');
            $table->foreignId('order_detail_id')->nullable()->constrained('orders_detail')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
            $table->enum('event_type', [
                'payment_receipt_generated',
                'contract_generated',
                'bsale_invoice_generated',
                'email_sent',
                'email_failed',
                'email_resent'
            ]);
            $table->enum('status', ['success', 'failed', 'skipped'])->default('success');
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('bsale_document_id')->nullable();
            $table->string('bsale_number')->nullable();
            $table->string('email_recipient')->nullable();
            $table->json('email_attachments')->nullable();
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Índices para optimizar búsquedas
            $table->index('payment_id');
            $table->index('order_detail_id');
            $table->index('event_type');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_confirmation_logs');
    }
};
