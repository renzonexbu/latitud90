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
        Schema::create('emergency_contact', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('code_phone');
            $table->string('phone');
            $table->string('country')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('relationship')->default('Familiar');
            $table->foreignId('participant_id')->constrained('participants');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_contact');
    }
};
