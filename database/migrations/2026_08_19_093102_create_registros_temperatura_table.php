<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registros de temperatura de la cadena de frío.
     */
    public function up(): void
    {
        Schema::create('registros_temperatura', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sucursal_id')->nullable();
            $table->string('tipo_registro', 20)->default('manual')->index();
            $table->string('ubicacion', 150)->nullable();
            $table->string('dispositivo', 150)->nullable();
            $table->decimal('temperatura', 5, 1);
            $table->decimal('humedad', 5, 1)->nullable();
            $table->decimal('temp_minima_aceptable', 5, 1)->nullable();
            $table->decimal('temp_maxima_aceptable', 5, 1)->nullable();
            $table->boolean('dentro_rango')->default(true);
            $table->string('observaciones')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->dateTime('registrado_en');
            $table->timestamps();

            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_temperatura');
    }
};