<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Equivalentes y sustitutos farmacológicos.
     *
     * Vinculación MANUAL producto→producto: un operador registra que el
     * producto B puede reemplazar al A. La decisión clínica queda siempre
     * en el farmacéutico; acá solo se documenta la relación y su factor
     * de conversión cuando corresponde (p. ej. presentaciones distintas).
     */
    public function up(): void
    {
        Schema::create('equivalencia_productos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('producto_id');
            $table->uuid('producto_equivalente_id');
            $table->string('tipo', 20)->default('equivalente')->index();
            $table->decimal('factor_conversion', 10, 2)->nullable();
            $table->text('notas')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('producto_equivalente_id')->references('id')->on('productos')->onDelete('cascade');

            $table->unique(['producto_id', 'producto_equivalente_id'], 'uq_equivalencia');
            // Evitar auto-referencias y duplicados en sentido inverso.
            $table->unique(['producto_equivalente_id', 'producto_id'], 'uq_equivalencia_inversa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equivalencia_productos');
    }
};