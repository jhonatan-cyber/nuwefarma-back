<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traslados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_traslado', 20);
            $table->uuid('sucursal_origen_id');
            $table->uuid('sucursal_destino_id');
            $table->uuid('usuario_solicita_id');
            $table->uuid('usuario_autoriza_id')->nullable();
            $table->uuid('usuario_recibe_id')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('sucursal_origen_id')->references('id')->on('sucursals')->onDelete('cascade');
            $table->foreign('sucursal_destino_id')->references('id')->on('sucursals')->onDelete('cascade');
            $table->foreign('usuario_solicita_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('usuario_autoriza_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('usuario_recibe_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->index(['estado']);
            $table->index(['created_at']);
        });

        Schema::create('traslado_detalles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('traslado_id');
            $table->uuid('lote_origen_id');
            $table->uuid('lote_destino_id')->nullable();
            $table->integer('cantidad');
            $table->integer('recibido')->default(0);
            $table->timestamps();

            $table->foreign('traslado_id')->references('id')->on('traslados')->onDelete('cascade');
            $table->foreign('lote_origen_id')->references('id')->on('lotes')->onDelete('cascade');
            $table->foreign('lote_destino_id')->references('id')->on('lotes')->onDelete('set null');
            $table->index(['traslado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traslado_detalles');
        Schema::dropIfExists('traslados');
    }
};
