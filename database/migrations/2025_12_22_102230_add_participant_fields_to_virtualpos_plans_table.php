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
        Schema::table('virtualpos_plans', function (Blueprint $table) {
            // Participante (null = plan general del program_course, con valor = plan personalizado)
            $table->foreignId('participant_id')->nullable()->after('program_course_id')
                ->constrained('participants')->nullOnDelete();

            // Índice único: un participante solo puede tener un plan activo por program_course
            $table->unique(['participant_id', 'program_course_id'], 'participant_program_course_unique');

            // Información del descuento (solo para planes personalizados)
            $table->decimal('original_price', 12, 2)->nullable()->after('trip_price');
            $table->decimal('discount_amount', 12, 2)->nullable()->after('original_price');
            $table->string('discount_type')->nullable()->after('discount_amount'); // scholarship, released, etc.
            $table->text('discount_reason')->nullable()->after('discount_type');

            // Auditoría
            $table->foreignId('created_by')->nullable()->after('api_response')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtualpos_plans', function (Blueprint $table) {
            $table->dropForeign(['participant_id']);
            $table->dropForeign(['created_by']);
            $table->dropUnique('participant_program_course_unique');
            $table->dropColumn([
                'participant_id',
                'original_price',
                'discount_amount',
                'discount_type',
                'discount_reason',
                'created_by'
            ]);
        });
    }
};
