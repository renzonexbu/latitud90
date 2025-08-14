<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('programs', function (Blueprint $table) {
			if (Schema::hasColumn('programs', 'total_payment_method_id')) {
				$table->dropForeign(['total_payment_method_id']);
				$table->dropColumn('total_payment_method_id');
			}
			if (Schema::hasColumn('programs', 'lat90_payment_method_id')) {
				$table->dropForeign(['lat90_payment_method_id']);
				$table->dropColumn('lat90_payment_method_id');
			}
		});
	}

	public function down(): void
	{
		Schema::table('programs', function (Blueprint $table) {
			$table->foreignId('total_payment_method_id')->nullable()->constrained('payment_methods');
			$table->foreignId('lat90_payment_method_id')->nullable()->constrained('payment_methods');
		});
	}
};


