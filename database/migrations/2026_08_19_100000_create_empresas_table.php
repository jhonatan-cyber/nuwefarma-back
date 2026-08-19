<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Datos de la empresa/organización para emisión fiscal boliviana.
     */
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nit', 30)->unique();
            $table->string('razon_social')->unique();
            $table->string('nombre_comercial')->nullable();
            $table->string('codigo_actividad', 20)->index();
            $table->string('descripcion_actividad')->nullable();
            $table->string('municipio', 100)->nullable();
            $table->string('departamento', 60)->nullable();
            $table->string('direccion')->nullable();
            $table->string('telefono', 40)->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('moneda', 3)->default('BOB');
            $table->enum('regimen', ['general', 'simplificado', 'integrado'])->default('general');
            $table->string('pais', 60)->default('Bolivia');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};