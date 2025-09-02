<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('program_payment_option', function (Blueprint $table) {
			$table->id();
			$table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
			$table->foreignId('payment_option_id')->constrained('payment_options')->onDelete('cascade');
			$table->boolean('enabled')->default(true);
			$table->boolean('manually_disabled')->default(false); // Campo para distinguir cambios manuales vs automáticos
			$table->timestamps();
			$table->unique(['program_id', 'payment_option_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('program_payment_option');
	}
};


