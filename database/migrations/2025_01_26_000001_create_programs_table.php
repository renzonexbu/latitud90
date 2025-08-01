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
            $table->string('name');
            $table->string('destination');
            $table->date('departure_date');
            $table->text('trip_description');
            $table->string('images_folder')->nullable(); // Dirección a carpeta en public
            $table->text('pillars')->nullable(); // Separados por coma
            $table->text('itinerary_description')->nullable();
            $table->string('itinerary_file')->nullable(); // Dirección a archivo PDF en public
            $table->string('travel_assistance_coverage')->nullable(); // Dirección a archivo
            $table->string('equipment_list')->nullable(); // Dirección a archivo
            $table->decimal('trip_price', 10, 2);
            $table->date('final_payment_date');
            $table->string('seller_name');
            $table->foreignId('payment_mode_id')->constrained('payment_modes');
            $table->boolean('active')->default(true);
            $table->timestamps();
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
