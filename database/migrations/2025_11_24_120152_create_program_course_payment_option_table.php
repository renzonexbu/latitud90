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
        Schema::create('program_course_payment_option', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_course_id')->constrained('program_courses')->onDelete('cascade');
            $table->foreignId('payment_option_id')->constrained('payment_options')->onDelete('cascade');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['program_course_id', 'payment_option_id'], 'program_course_payment_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_course_payment_option');
    }
};
