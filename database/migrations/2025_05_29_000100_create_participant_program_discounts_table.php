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
		Schema::create('participant_program_discounts', function (Blueprint $table) {
			$table->id();
			$table->foreignId('participant_program_id')->constrained('participant_program')->onDelete('cascade');
			$table->enum('type', ['sibling', 'with_parents', 'scholarship', 'exemption']);
			$table->decimal('percent', 5, 2)->nullable();
			$table->decimal('amount', 10, 2)->nullable();
			$table->date('effective_from')->nullable();
			$table->date('effective_to')->nullable();
			$table->enum('status', ['active', 'revoked'])->default('active');
			$table->text('comment')->nullable();
			$table->foreignId('approved_by')->nullable()->constrained('users');
			$table->timestamps();

			$table->index(['participant_program_id', 'status'], 'ppd_ppid_status_idx');
			$table->index(['effective_from'], 'ppd_eff_from_idx');
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('participant_program_discounts');
	}
};


