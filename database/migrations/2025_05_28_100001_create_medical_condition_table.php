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
        Schema::create('medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants')->onDelete('cascade');
            $table->enum('type', ['dietary_restriction', 'intolerance', 'allergy', 'medical_condition']);
            $table->string('description');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['participant_id', 'type']); // Para búsquedas por participante y tipo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_conditions');
    }
};
