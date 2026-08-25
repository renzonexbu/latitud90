<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardian_users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['comune_id']);
        });

        Schema::table('guardian_users', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->change();
            $table->unsignedBigInteger('comune_id')->nullable()->change();
        });

        Schema::table('guardian_users', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->foreign('comune_id')->references('id')->on('comunes')->nullOnDelete();
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['comune_id']);
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->change();
            $table->unsignedBigInteger('comune_id')->nullable()->change();
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->nullOnDelete();
            $table->foreign('comune_id')->references('id')->on('comunes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guardian_users', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['comune_id']);
        });

        Schema::table('guardian_users', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable(false)->change();
            $table->unsignedBigInteger('comune_id')->nullable(false)->change();
        });

        Schema::table('guardian_users', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions');
            $table->foreign('comune_id')->references('id')->on('comunes');
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['comune_id']);
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable(false)->change();
            $table->unsignedBigInteger('comune_id')->nullable(false)->change();
        });

        Schema::table('frequent_client', function (Blueprint $table) {
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('comune_id')->references('id')->on('comunes')->onDelete('cascade');
        });
    }
};
