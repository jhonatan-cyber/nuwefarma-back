<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de movimientos de inventario (Kardex) para trazabilidad completa.
     */
    public function up(): void
    {
        Schema::create('movimientos_lote', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Referencia al lote
            $table->uuid('lote_id');
            $table->index('lote_id');

            // Tipo de movimiento (string en lugar de enum para compatibilidad)
            $table->string('tipo_movimiento', 50);

            // Cantidades
            $table->integer('cantidad');
            $table->integer('stock_anterior');
            $table->integer('stock_nuevo');

            // Documento de referencia
            $table->string('documento_tipo', 50)->nullable();
            $table->uuid('documento_id')->nullable();
            $table->string('documento_numero', 100)->nullable();

            // Usuario responsable
            $table->uuid('usuario_id')->nullable();
            $table->index('usuario_id');

            // Sucursal donde se realiza el movimiento
            $table->uuid('sucursal_id')->nullable();
            $table->index('sucursal_id');

            // Datos del producto (para histórico)
            $table->string('producto_nombre', 255)->nullable();
            $table->string('producto_codigo', 100)->nullable();

            // Costos (para análisis)
            $table->decimal('costo_unitario', 10, 2)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();

            // Observaciones
            $table->text('observaciones')->nullable();

            // Timestamps
            $table->timestamps();

            // Índices para consultas rápidas
            $table->index('tipo_movimiento');
            $table->index('documento_id');
            $table->index('created_at');
            $table->index(['lote_id', 'created_at']);
            $table->index(['tipo_movimiento', 'created_at']);
        });

        // Agregar foreign keys después de crear la tabla
        Schema::table('movimientos_lote', function (Blueprint $table) {
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_lote');
    }
};
