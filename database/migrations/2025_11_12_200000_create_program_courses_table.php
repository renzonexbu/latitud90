<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('program_courses', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');

            // Campos del plan específico
            $table->string('code', 8)->unique(); // Código único del plan
            $table->string('name')->nullable(); // Nombre generado automáticamente
            $table->date('departure_date'); // Fecha de salida
            $table->decimal('trip_price', 10, 2); // Precio del viaje
            $table->date('final_payment_date'); // Fecha final de pago
            $table->smallInteger('year'); // Año

            // Vendedor y ejecutivo
            $table->string('seller_name')->nullable();
            $table->foreignId('sales_executive_id')->nullable()->constrained('sales_executives');

            // Configuración de pagos
            $table->boolean('enable_total_payment')->default(false);
            $table->boolean('enable_subscription_payment')->default(false);
            $table->integer('subscription_max_months')->nullable();

            // VirtualPos
            $table->string('virtualpos_plan_id')->nullable();

            // Descuentos
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();

            // Estado y auditoría
            $table->enum('status', ['reserva', 'vigente', 'ejecutado'])->default('reserva');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            // Índices
            $table->index(['program_id', 'course_id']);
            $table->index(['code']);
            $table->index(['departure_date']);
            $table->index(['status']);
            $table->index(['active', 'departure_date']);
            $table->index(['status', 'departure_date']);
            $table->index(['virtualpos_plan_id']);
        });

        // Crear trigger para actualizar status a 'ejecutado' cuando pase la fecha de salida
        DB::unprepared('
            CREATE TRIGGER update_program_course_status_to_ejecutado
            BEFORE UPDATE ON program_courses
            FOR EACH ROW
            BEGIN
                IF NEW.departure_date < CURDATE() AND NEW.status != "ejecutado" THEN
                    SET NEW.status = "ejecutado";
                    SET NEW.active = false;
                END IF;
            END;
        ');

        // Crear trigger para insertar
        DB::unprepared('
            CREATE TRIGGER insert_program_course_status_check
            BEFORE INSERT ON program_courses
            FOR EACH ROW
            BEGIN
                IF NEW.departure_date < CURDATE() THEN
                    SET NEW.status = "ejecutado";
                    SET NEW.active = false;
                ELSEIF YEAR(NEW.departure_date) > YEAR(CURDATE()) THEN
                    SET NEW.status = "reserva";
                ELSE
                    SET NEW.status = "vigente";
                END IF;
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar triggers antes de eliminar la tabla
        DB::unprepared('DROP TRIGGER IF EXISTS update_program_course_status_to_ejecutado');
        DB::unprepared('DROP TRIGGER IF EXISTS insert_program_course_status_check');

        Schema::dropIfExists('program_courses');
    }
};
