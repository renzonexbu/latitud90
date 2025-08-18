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
        Schema::create('pending_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_detail_id')->constrained('orders_detail')->onDelete('cascade');
            $table->string('gateway_type'); // 'transbank' o 'khipu'
            $table->json('gateway_data'); // token_ws, payment_id, etc.
            $table->string('status')->default('pending'); // pending, confirmed, failed
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_detail_id', 'status']);
            $table->index(['status', 'attempts']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_payments');
    }
};
