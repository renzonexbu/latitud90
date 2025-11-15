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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
            $table->enum('education_level', ['preescolar', 'basica', 'media', 'universitaria']);
            $table->enum('grade', ['A', 'B', 'C', 'D', 'E']);
            $table->string('year', 4);
            $table->unsignedTinyInteger('course_number')->nullable();
            $table->string('course_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('students_file_path')->nullable();
            $table->string('students_file_name')->nullable();
            $table->integer('total_students')->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['institution_id']); // Para joins con institutions
            $table->index(['status']); // Para filtros por status
            $table->index(['created_at']); // Para filtros por fecha
            $table->index(['status', 'created_at']); // Para filtros combinados
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};