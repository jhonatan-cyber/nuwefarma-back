<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Productos prescritos en una receta con dispensación total o parcial.
     */
    public function up(): void
    {
        Schema::create('receta_productos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('receta_id');
            $table->uuid('producto_id');
            $table->decimal('cantidad_prescrita', 10, 2);
            $table->decimal('cantidad_dispensada', 10, 2)->default(0);
            $table->string('posologia', 500)->nullable();
            $table->string('duracion', 100)->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->timestamps();

            $table->foreign('receta_id')->references('id')->on('recetas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

            $table->index(['receta_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receta_productos');
    }
};