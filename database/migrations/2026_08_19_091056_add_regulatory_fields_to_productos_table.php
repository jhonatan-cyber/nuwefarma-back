<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ampliar productos con atributos farmacéuticos regulatorios: principio
     * activo y condición de venta (base para medicamentos controlados).
     */
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('principio_activo')->nullable()->after('nombre');
            $table->string('condicion_venta', 30)->default('venta_libre')->after('registro_sanitario');

            $table->index('condicion_venta');
            $table->index('principio_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['condicion_venta']);
            $table->dropIndex(['principio_activo']);
            $table->dropColumn(['condicion_venta', 'principio_activo']);
        });
    }
};