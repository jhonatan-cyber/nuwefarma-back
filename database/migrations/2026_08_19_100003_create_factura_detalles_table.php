<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de detalle de las facturas fiscales.
     */
    public function up(): void
    {
        Schema::create('factura_detalles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('factura_id');
            $table->uuid('producto_id')->nullable();

            $table->string('codigo_producto', 50)->nullable();
            $table->string('descripcion')->nullable();
            $table->integer('cantidad')->default(1);
            $table->string('unidad_medida', 20)->default('UNIDAD');
            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('descuento_unitario', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('monto_descuento', 15, 2)->default(0);

            $table->timestamps();

            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('set null');

            $table->index(['factura_id']);
            $table->index(['producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_detalles');
    }
};