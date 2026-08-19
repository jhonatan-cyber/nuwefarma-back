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
        Schema::create('arqueos_caja', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_arqueo')->unique();
            $table->uuid('caja_id');
            $table->decimal('saldo_inicial', 15, 2);
            $table->decimal('total_ingresos', 15, 2);
            $table->decimal('total_egresos', 15, 2);
            $table->decimal('saldo_sistema', 15, 2);
            $table->decimal('total_declarado', 15, 2);
            $table->decimal('total_contado', 15, 2);
            $table->decimal('diferencia', 15, 2);
            $table->json('detalles')->nullable();
            $table->string('estado', 20)->default('realizado'); // realizado | conciliado
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_cierre')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['caja_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arqueos_caja');
    }
};