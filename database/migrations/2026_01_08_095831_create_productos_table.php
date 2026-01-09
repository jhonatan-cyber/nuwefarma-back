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
        Schema::create('productos', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relaciones
            $table->uuid('categoria_id')->nullable();

            // Identificación
            $table->string('nombre');
            $table->string('codigo_barras')->nullable()->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('codigo_interno')->nullable()->unique();

            // Detalles farmacéuticos
            $table->string('laboratorio')->nullable();
            $table->string('forma_farmaceutica')->nullable(); // ej. tabletas, jarabe, ampolla
            $table->string('concentracion')->nullable();      // ej. 500 mg, 5 mg/mL
            $table->string('presentacion')->nullable();       // ej. Caja x 10, Frasco 100 mL
            $table->string('via_administracion')->nullable(); // oral, IV, IM, etc.
            $table->string('unidad_medida')->nullable();      // unidad, caja, frasco
            $table->integer('fracciones_por_unidad')->nullable();
            $table->boolean('permite_fraccionar')->default(false);

            // Trazabilidad y vencimiento
            $table->string('lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('registro_sanitario')->nullable();
            $table->boolean('refrigeracion_requerida')->default(false);
            $table->integer('dias_para_alertar_vencimiento')->default(60);

            // Inventario
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->integer('stock_maximo')->nullable();

            // Precios e impuestos
            $table->decimal('precio_compra', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->decimal('margen_sugerido', 5, 2)->nullable();
            $table->decimal('impuesto', 5, 2)->nullable();

            // Metadatos
            $table->json('etiquetas')->nullable();
            $table->json('fotos')->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            $table->timestamps();

            // Índices y llaves
            $table->index('categoria_id');
            $table->index('nombre');
            $table->index('fecha_vencimiento');
            $table->index('estado');
            $table->foreign('categoria_id')->references('id')->on('categorias')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
