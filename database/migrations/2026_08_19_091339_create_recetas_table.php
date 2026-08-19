<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recetas médicas: vínculo entre médico y paciente con los productos
     * prescritos. El estado controla pendiente / parcial / atendida /
     * vencida / anulada.
     */
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_receta')->unique()->index();
            $table->date('fecha_emision')->index();
            $table->date('fecha_vencimiento')->nullable()->index();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->uuid('medico_id')->nullable();
            $table->uuid('paciente_id')->nullable();
            $table->uuid('usuario_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('medico_id')->references('id')->on('medicos')->onDelete('set null');
            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['medico_id', 'estado']);
            $table->index(['paciente_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};