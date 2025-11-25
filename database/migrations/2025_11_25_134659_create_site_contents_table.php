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
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('section'); // hero, schools, transform, experiences, courses, faq, footer
            $table->string('key'); // identificador único dentro de la sección
            $table->string('type')->default('text'); // text, textarea, image, html
            $table->text('value')->nullable(); // contenido o ruta de imagen
            $table->string('label')->nullable(); // etiqueta para el admin
            $table->integer('order')->default(0); // orden dentro de la sección
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['section', 'key']);
            $table->index(['section', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
