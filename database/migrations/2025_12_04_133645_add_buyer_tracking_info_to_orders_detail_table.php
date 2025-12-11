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
        Schema::table('orders_detail', function (Blueprint $table) {
            // Información de identificación del dispositivo/conexión
            $table->string('ip_address', 45)->nullable()->after('gateway_response')->comment('Dirección IP del comprador (IPv4 o IPv6)');
            $table->text('user_agent')->nullable()->after('ip_address')->comment('User Agent del navegador');
            $table->string('device_type', 20)->nullable()->after('user_agent')->comment('Tipo de dispositivo: desktop, mobile, tablet');
            $table->string('browser', 50)->nullable()->after('device_type')->comment('Navegador utilizado');
            $table->string('operating_system', 50)->nullable()->after('browser')->comment('Sistema operativo');

            // Geolocalización aproximada (basada en IP)
            $table->string('geo_country', 100)->nullable()->after('operating_system')->comment('País detectado por IP');
            $table->string('geo_region', 100)->nullable()->after('geo_country')->comment('Región/Estado detectado por IP');
            $table->string('geo_city', 100)->nullable()->after('geo_region')->comment('Ciudad detectada por IP');

            // Timestamp exacto de aceptación de términos
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted_confirmation')->comment('Fecha/hora exacta de aceptación de términos');

            // Referencia de origen
            $table->string('referrer_url', 500)->nullable()->after('geo_city')->comment('URL de referencia (de dónde vino el comprador)');
            $table->string('utm_source', 100)->nullable()->after('referrer_url')->comment('UTM Source para tracking de marketing');
            $table->string('utm_medium', 100)->nullable()->after('utm_source')->comment('UTM Medium para tracking de marketing');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium')->comment('UTM Campaign para tracking de marketing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_detail', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'user_agent',
                'device_type',
                'browser',
                'operating_system',
                'geo_country',
                'geo_region',
                'geo_city',
                'terms_accepted_at',
                'referrer_url',
                'utm_source',
                'utm_medium',
                'utm_campaign',
            ]);
        });
    }
};
