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
            $table->string('year', 4);
            $table->unsignedTinyInteger('course_number')->nullable();
            $table->string('course_name')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('students_file_path')->nullable();
            $table->string('students_file_name')->nullable();
            $table->integer('total_students')->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('target_amount', 10, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['program_id']); // Para joins con programs
            $table->index(['institution_id']); // Para joins con institutions
            $table->index(['status']); // Para filtros por status
            $table->index(['created_at']); // Para filtros por fecha
            $table->index(['status', 'created_at']); // Para filtros combinados
        });

        // Agregar el campo course_id a la tabla programs
        Schema::table('programs', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el campo course_id de la tabla programs
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        Schema::dropIfExists('courses');
    }
};