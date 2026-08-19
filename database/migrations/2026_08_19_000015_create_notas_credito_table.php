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
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->unique();
            $table->string('documento_tipo', 20)->index(); // Venta | Compra
            $table->uuid('documento_id');
            $table->string('documento_numero', 50)->nullable();
            $table->decimal('monto', 15, 2);
            $table->text('motivo')->nullable();
            $table->string('estado', 20)->default('emitida'); // emitida | aplicada | anulada
            $table->string('aplicado_a', 100)->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};