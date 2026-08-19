<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de la orden de compra con cantidad solicitada, recibida y precios.
     */
    public function up(): void
    {
        Schema::create('orden_compra_productos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('orden_id');
            $table->uuid('producto_id');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('cantidad_recibida', 10, 2)->default(0);
            $table->decimal('precio_unitario', 12, 4)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0)->comment('cantidad * precio_unitario');
            $table->uuid('lote_id')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->timestamps();

            $table->foreign('orden_id')->references('id')->on('ordenes_compra')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_productos');
    }
};