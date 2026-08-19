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
        Schema::create('pagos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_pago')->unique();
            $table->string('documento_tipo', 20)->index(); // Venta | Compra
            $table->uuid('documento_id');
            $table->string('documento_numero', 50)->nullable();
            $table->timestamp('fecha_pago')->useCurrent();
            $table->decimal('monto', 15, 2);
            $table->string('metodo_pago', 20)->index(); // efectivo | tarjeta | transferencia | cheque
            $table->string('tipo_pago', 20)->index(); // inicial | abono | total | parcial
            $table->string('referencia', 100)->nullable();
            $table->text('nota')->nullable();
            $table->uuid('caja_id')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['documento_tipo', 'documento_id', 'fecha_pago']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};