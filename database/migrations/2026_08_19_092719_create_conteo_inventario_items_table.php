<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ítems del conteo: producto+lote con stock sistema vs stock físico.
     */
    public function up(): void
    {
        Schema::create('conteo_inventario_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conteo_id');
            $table->uuid('producto_id');
            $table->uuid('lote_id')->nullable();
            $table->integer('stock_sistema')->default(0);
            $table->integer('stock_fisico')->nullable();
            $table->integer('diferencia')->default(0);
            $table->string('estado', 20)->default('pendiente')->index();
            $table->text('observaciones')->nullable();
            $table->uuid('contado_por_id')->nullable();
            $table->timestamps();

            $table->foreign('conteo_id')->references('id')->on('conteos_inventario')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('set null');
            $table->foreign('contado_por_id')->references('id')->on('usuarios')->onDelete('set null');

            $table->unique(['conteo_id', 'lote_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conteo_inventario_items');
    }
};