<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 20);
            $table->string('motivo', 100);
            $table->text('observaciones')->nullable();
            $table->uuid('usuario_id');
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
            $table->index(['tipo']);
            $table->index(['created_at']);
        });

        Schema::create('ajuste_inventario_detalles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('ajuste_id');
            $table->uuid('lote_id');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');
            $table->integer('diferencia');
            $table->timestamps();

            $table->foreign('ajuste_id')->references('id')->on('ajustes_inventario')->onDelete('cascade');
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('cascade');
            $table->index(['ajuste_id']);
            $table->index(['lote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajuste_inventario_detalles');
        Schema::dropIfExists('ajustes_inventario');
    }
};
