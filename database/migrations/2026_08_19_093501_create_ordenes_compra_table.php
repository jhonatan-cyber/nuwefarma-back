<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Órdenes de compra y solicitudes de abastecimiento.
     */
    public function up(): void
    {
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_orden', 30)->unique();
            $table->string('tipo', 20)->default('solicitud')->index();
            $table->string('prioridad', 20)->default('normal')->index();
            $table->string('estado', 30)->default('borrador')->index();
            $table->uuid('proveedor_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('aprobado_por_id')->nullable();
            $table->uuid('recibido_por_id')->nullable();
            $table->date('fecha_solicitud')->nullable();
            $table->date('fecha_estimada')->nullable();
            $table->date('fecha_recepcion')->nullable();
            $table->dateTime('fecha_aprobacion')->nullable();
            $table->dateTime('fecha_envio')->nullable();
            $table->dateTime('fecha_finalizacion')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('impuestos', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notas')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('aprobado_por_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('recibido_por_id')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};