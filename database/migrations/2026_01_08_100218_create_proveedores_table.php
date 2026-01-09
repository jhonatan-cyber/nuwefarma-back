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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('nombre');
            $table->string('nit')->nullable()->unique();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->nullable();
            $table->string('contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->string('categoria')->nullable(); // ej. laboratorio, distribuidor
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            $table->timestamps();

            $table->index('nombre');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
