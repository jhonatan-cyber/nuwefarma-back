<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puntos de venta registrados en el SIAT por sucursal.
     */
    public function up(): void
    {
        Schema::create('puntos_venta', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sucursal_id')->nullable();
            $table->string('codigo_poa', 20)->unique();
            $table->string('nombre')->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('direccion')->nullable();
            $table->enum('tipo', ['fisica', 'web'])->default('fisica');
            $table->enum('ambiente', ['pruebas', 'produccion'])->default('pruebas');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntos_venta');
    }
};