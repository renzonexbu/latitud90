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
			$table->string('name'); // Nombre de la plantilla (ej: "Viaje al Norte de Chile")
            $table->string('destination'); // Destino
            $table->text('trip_description')->nullable(); // Descripción del viaje
            $table->string('images_folder')->nullable(); // Carpeta de imágenes
            $table->text('pillars')->nullable(); // Pilares educativos
            $table->text('itinerary_description')->nullable(); // Descripción del itinerario
            $table->string('itinerary_file')->nullable(); // PDF del itinerario
            $table->string('travel_assistance_coverage')->nullable(); // PDF cobertura
            $table->string('equipment_list')->nullable(); // PDF lista de equipo

            // Campos de auditoría
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Índices
            $table->index(['destination']);
            $table->index(['active']);
            $table->index(['created_at']);
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
