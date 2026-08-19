<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conteos de inventario físico y cíclico.
     */
    public function up(): void
    {
        Schema::create('conteos_inventario', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_conteo', 30)->unique();
            $table->uuid('sucursal_id')->nullable();
            $table->string('tipo', 20)->default('fisico')->index();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->date('fecha_programada')->nullable();
            $table->date('fecha_ejecucion')->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->integer('total_items')->default(0);
            $table->integer('items_conteados')->default(0);
            $table->integer('items_con_diferencia')->default(0);
            $table->decimal('total_diferencia', 14, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->uuid('responsable_id')->nullable();
            $table->uuid('cerrado_por_id')->nullable();
            $table->timestamps();

            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
            $table->foreign('responsable_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('cerrado_por_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conteos_inventario');
    }
};