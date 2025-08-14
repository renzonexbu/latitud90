<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('orders_detail', function (Blueprint $table) {
			if (!Schema::hasColumn('orders_detail', 'payment_option_id')) {
				$table->foreignId('payment_option_id')->nullable()->after('order_id')->constrained('payment_options');
			}
			if (Schema::hasColumn('orders_detail', 'payment_method_id')) {
				$table->dropForeign(['payment_method_id']);
				$table->dropColumn('payment_method_id');
			}
			if (Schema::hasColumn('orders_detail', 'payment_mode_id')) {
				$table->dropForeign(['payment_mode_id']);
				$table->dropColumn('payment_mode_id');
			}
		});
	}

	public function down(): void
	{
		Schema::table('orders_detail', function (Blueprint $table) {
			if (Schema::hasColumn('orders_detail', 'payment_option_id')) {
				$table->dropForeign(['payment_option_id']);
				$table->dropColumn('payment_option_id');
			}
			$table->foreignId('payment_method_id')->constrained('payment_methods');
			$table->foreignId('payment_mode_id')->constrained('payment_modes');
		});
	}
};


