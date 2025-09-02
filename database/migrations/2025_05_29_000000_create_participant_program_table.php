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
		Schema::create('participant_program', function (Blueprint $table) {
			$table->id();
			$table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
			$table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
			$table->string('enrollment_code', 20)->unique();
			$table->decimal('individual_price', 10, 2)->nullable();
			$table->enum('status', ['pending_payment', 'confirmed', 'cancelled'])->default('pending_payment');
			$table->timestamps();

			$table->unique(['participant_id', 'program_id']);
			$table->index(['status']);
			$table->index(['created_at']); // Para filtros por fecha
			$table->index(['status', 'created_at']); // Para filtros combinados
			$table->index(['participant_id', 'created_at']); // Para consultas por participante
			$table->index(['program_id', 'created_at']); // Para consultas por programa
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('participant_program');
	}
};


