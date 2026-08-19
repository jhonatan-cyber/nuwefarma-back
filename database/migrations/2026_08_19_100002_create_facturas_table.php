<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Facturas emitidas o anuladas ante el SIAT.
     */
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('venta_id')->nullable();
            $table->uuid('punto_venta_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->uuid('usuario_id')->nullable();

            $table->string('numero_factura', 20)->index();
            $table->string('cuf', 64)->unique();
            $table->string('cufd', 64)->nullable();
            $table->string('cuis', 64)->nullable();
            $table->string('numero_autorizacion', 20)->nullable();
            $table->string('codigo_control', 20)->nullable();

            $table->enum('tipo_emision', ['online', 'contingencia'])->default('online')->index();
            $table->enum('tipo_documento_sector', ['factura_con_credito', 'factura_sin_credito', 'liquidacion', 'otros'])->default('factura_con_credito');
            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque', 'mixto', 'otro'])->default('efectivo');

            $table->string('nit_cliente', 20)->nullable();
            $table->string('razon_social_cliente')->nullable();
            $table->string('codigo_cliente', 30)->nullable();
            $table->string('complemento_ci', 20)->nullable();
            $table->string('direccion_cliente')->nullable();
            $table->string('email_cliente')->nullable();

            $table->date('fecha_emision');
            $table->string('leyenda', 500)->nullable();
            $table->string('numero_serie', 30)->nullable();
            $table->string('numero_duplicado', 30)->nullable();

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('descuento', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);
            $table->decimal('monto_sujeto_iva', 15, 2)->default(0);
            $table->decimal('monto_no_sujeto', 15, 2)->default(0);
            $table->decimal('monto_ice', 15, 2)->default(0);

            $table->enum('estado', ['emitida', 'anulada', 'rechazada', 'en_proceso'])->default('emitida')->index();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->dateTime('fecha_anulacion')->nullable();
            $table->text('respuesta_siat')->nullable();
            $table->text('qr')->nullable();

            $table->timestamps();

            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('set null');
            $table->foreign('punto_venta_id')->references('id')->on('puntos_venta')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');

            $table->unique(['punto_venta_id', 'numero_factura']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};