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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('code_phone');
            $table->string('phone');
            $table->string('document_type');
            $table->string('document_number');
            $table->string('country');
            $table->date('birth_date');
            $table->text('address')->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->enum('status', ['pending_payment', 'confirmed', 'cancelled']);
            $table->datetime('registration_date');
            $table->decimal('individual_price', 10, 2);
            $table->decimal('price_adjustments', 10, 2)->default(0);
            $table->text('adjustment_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('participants_emergency_contact', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants');
            $table->foreignId('emergency_contact_id')->constrained('emergency_contact');
            $table->timestamps();
        });

        Schema::create('participants_medical_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('participants');
            $table->foreignId('medical_condition_id')->constrained('medical_conditions');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
