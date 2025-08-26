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
		Schema::create('payment_options', function (Blueprint $table) {
			$table->id();
			$table->string('code')->unique(); // e.g. full_transfer_khipu, full_debit_webpay, full_credit_webpay_3, lat90_12
			$table->string('label'); // Texto a mostrar en el admin/frontend
			$table->enum('mode', ['full', 'lat90']);
			$table->string('gateway_code')->nullable(); // transbank | khipu | null
			$table->string('report_code')->nullable();
			$table->unsignedTinyInteger('installments')->nullable(); // 3,6,9,12 para crédito/lat90
			$table->boolean('active')->default(true);
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('payment_options');
	}
};


