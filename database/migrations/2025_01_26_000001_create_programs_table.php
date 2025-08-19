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
        Schema::create('programs', function (Blueprint $table) {
			$table->id();
			$table->foreignId('institution_id')->constrained('institutions')->onDelete('cascade');
			$table->string('code', 8); // Código del programa (4 dígitos hoy, capacidad hasta 8)
			$table->string('name');
            $table->string('destination');
            $table->date('departure_date');
            $table->text('trip_description')->nullable();
            $table->string('images_folder')->nullable();
            $table->text('pillars')->nullable();
            $table->text('itinerary_description')->nullable();
            $table->string('itinerary_file')->nullable();
            $table->string('travel_assistance_coverage')->nullable();
            $table->string('equipment_list')->nullable();
            $table->decimal('trip_price', 10, 2);
			$table->smallInteger('year');
			$table->string('grade')->nullable();
            $table->date('final_payment_date')->nullable();
			$table->string('seller_name')->nullable();
			$table->foreignId('sales_executive_id')->constrained('sales_executives');

			// Configuración de pago total
			$table->boolean('enable_total_payment')->default(false);

			// Configuración de pago mensual Lat90
			$table->boolean('enable_lat90_payment')->default(false);
			$table->integer('lat90_max_installments')->nullable();

            // Campos de descuento
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->boolean('active')->default(true);
            $table->enum('status', ['reserva', 'realizado'])->nullable();
            $table->timestamps();

            // Índices
			$table->index(['active', 'departure_date']);
			$table->index(['status', 'departure_date']);
			$table->index(['code']);
            $table->index(['destination']);
        });

        // Crear trigger para actualizar status a 'realizado' cuando pase la fecha de salida
        DB::unprepared('
            CREATE TRIGGER update_program_status_to_realizado
            BEFORE UPDATE ON programs
            FOR EACH ROW
            BEGIN
                IF NEW.departure_date < CURDATE() AND NEW.status != "realizado" THEN
                    SET NEW.status = "realizado";
                END IF;
            END;
        ');

        // Crear trigger para insertar
        DB::unprepared('
            CREATE TRIGGER insert_program_status_check
            BEFORE INSERT ON programs
            FOR EACH ROW
            BEGIN
                IF NEW.departure_date < CURDATE() THEN
                    SET NEW.status = "realizado";
                ELSEIF NEW.departure_date > DATE_ADD(CURDATE(), INTERVAL 1 YEAR) THEN
                    SET NEW.status = "reserva";
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
        DB::unprepared('DROP TRIGGER IF EXISTS update_program_status_to_realizado');
        DB::unprepared('DROP TRIGGER IF EXISTS insert_program_status_check');
        
        Schema::dropIfExists('programs');
    }
};
