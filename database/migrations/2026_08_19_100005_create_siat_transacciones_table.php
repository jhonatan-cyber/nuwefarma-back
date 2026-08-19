<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora auditable de solicitudes y respuestas al provider fiscal,
     * con idempotencia para evitar procesamiento doble.
     */
    public function up(): void
    {
        Schema::create('siat_transacciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('uuid_request', 64)->unique();
            $table->enum('tipo_operacion', [
                'solicitar_cuis',
                'solicitar_cufd',
                'emitir',
                'anular',
                'consultar',
                'reversion_anulacion',
            ])->index();
            $table->uuid('factura_id')->nullable();
            $table->uuid('punto_venta_id')->nullable();
            $table->string('cuf', 64)->nullable()->index();
            $table->enum('estado', ['exito', 'error'])->default('exito');
            $table->string('codigo_respuesta', 20)->nullable();
            $table->string('descripcion', 1000)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->ipAddress('ip_origen')->nullable();
            $table->timestamps();

            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('set null');
            $table->foreign('punto_venta_id')->references('id')->on('puntos_venta')->onDelete('set null');

            $table->index(['tipo_operacion', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siat_transacciones');
    }
};