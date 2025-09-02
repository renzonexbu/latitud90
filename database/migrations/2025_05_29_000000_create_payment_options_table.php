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
			$table->string('code')->unique(); // e.g. full_transfer_khipu, full_debit_credit_3, full_international, lat90_transfer_khipu, lat90_debit_credit_0
			$table->string('label'); // Texto a mostrar en el admin/frontend
			$table->enum('mode', ['full', 'lat90']);
			$table->string('gateway_code')->nullable(); // transbank | khipu | null
			$table->string('report_code')->nullable();
			$table->unsignedTinyInteger('installments')->nullable(); // 0/3/6/9/12 SOLO para opciones full_*; en lat90, null/0
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


