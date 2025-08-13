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
        Schema::create('programs', function (Blueprint $table) {
			$table->id();
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
            $table->foreignId('total_payment_method_id')->nullable()->constrained('payment_methods');

            // Configuración de pago mensual Lat90
            $table->boolean('enable_lat90_payment')->default(false);
            $table->foreignId('lat90_payment_method_id')->nullable()->constrained('payment_methods');
            $table->integer('lat90_max_installments')->nullable();

            // Campos de descuento
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Índices
			$table->index(['active', 'departure_date']);
			$table->index(['code']);
            $table->index(['destination']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
