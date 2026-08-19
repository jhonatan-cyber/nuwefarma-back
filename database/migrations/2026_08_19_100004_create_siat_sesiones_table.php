<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesiones SIAT vigentes: CUIS (anual) y CUFD (diario) por punto de venta.
     */
    public function up(): void
    {
        Schema::create('siat_sesiones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('punto_venta_id')->nullable();
            $table->enum('tipo', ['cuis', 'cufd'])->index();
            $table->string('codigo', 64);
            $table->string('codigo_control', 20)->nullable();
            $table->string('descripcion')->nullable();
            $table->date('fecha_vigencia')->nullable();
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');

            $table->timestamps();

            $table->foreign('punto_venta_id')->references('id')->on('puntos_venta')->onDelete('cascade');

            $table->index(['punto_venta_id', 'tipo', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siat_sesiones');
    }
};