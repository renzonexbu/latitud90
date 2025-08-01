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
            $table->string('institution_name');
            $table->enum('education_level', ['preescolar', 'primaria', 'secundaria', 'universitaria']);
            $table->string('year', 4);
            $table->string('grade');
            $table->enum('shift', ['mañana', 'tarde', 'noche']);
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->foreignId('program_id')->nullable()->constrained('programs')->onDelete('set null');
            $table->date('end_date');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('students_file_path')->nullable();
            $table->integer('total_students')->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('target_amount', 10, 2)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
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