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
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('caja_id');
            $table->string('tipo', 10)->index(); // ingreso | egreso
            $table->string('origen', 30)->index(); // venta | compra | gasto | abono | pago | apertura | ajuste_arqueo | nota_credito
            $table->string('documento_tipo', 20)->nullable();
            $table->uuid('documento_id')->nullable();
            $table->string('documento_numero', 50)->nullable();
            $table->decimal('monto', 15, 2);
            $table->decimal('saldo_despues', 15, 2)->nullable();
            $table->text('concepto')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['caja_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};