<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro de movimientos de medicamentos controlados (receta retenida),
     * obligatorio en la práctica farmacéutica boliviana para tranquilizantes,
     * estupefacientes y psicotrópicos.
     */
    public function up(): void
    {
        Schema::create('libro_controlados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('producto_id');
            $table->uuid('lote_id')->nullable();
            $table->uuid('receta_id')->nullable();
            $table->string('receta_numero', 50)->nullable();
            $table->uuid('paciente_id')->nullable();
            $table->uuid('medico_id')->nullable();
            $table->decimal('cantidad', 10, 2);
            $table->string('autorizacion', 100)->nullable();
            $table->string('tipo_operacion', 20)->default('dispensacion')->index();
            $table->text('observaciones')->nullable();
            $table->uuid('cajero_id')->nullable();
            $table->uuid('sucursal_id')->nullable();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('set null');
            $table->foreign('receta_id')->references('id')->on('recetas')->onDelete('set null');
            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('set null');
            $table->foreign('medico_id')->references('id')->on('medicos')->onDelete('set null');
            $table->foreign('cajero_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('sucursal_id')->references('id')->on('sucursals')->onDelete('set null');

            $table->index(['producto_id', 'created_at']);
            $table->index(['sucursal_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libro_controlados');
    }
};