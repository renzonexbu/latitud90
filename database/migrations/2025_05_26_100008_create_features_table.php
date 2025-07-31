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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('programs_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs');
            $table->foreignId('feature_id')->constrained('features');
            $table->enum('type', ['include', 'exclude']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
