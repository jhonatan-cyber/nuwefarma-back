<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de lotes para control de inventario por número de lote y fecha de vencimiento.
     */
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relación con producto (un producto puede tener muchos lotes)
            $table->uuid('producto_id');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

            // Identificación del lote
            $table->string('numero_lote', 100);
            $table->date('fecha_vencimiento');

            // Control de stock por lote
            $table->integer('stock')->default(0);                    // Stock actual
            $table->integer('stock_comprometido')->default(0);       // Reservado para ventas
            $table->integer('stock_minimo')->default(5);             // Alerta de stock bajo
            $table->integer('stock_maximo')->nullable();             // Capacidad máxima

            // Información de costos
            $table->decimal('precio_costo', 10, 2)->default(0);      // Costo de este lote
            $table->decimal('precio_costo_promedio', 10, 2)->default(0);

            // Estado del lote
            $table->enum('estado', ['disponible', 'parcial', 'agotado', 'vencido', 'retirado'])->default('disponible');

            // Información de origen
            $table->string('proveedor_id')->nullable();              // Proveedor del lote
            $table->uuid('compra_id')->nullable();                   // Compra de origen
            $table->string('documento_origen')->nullable();          // Factura, NCF, etc.

            // Ubicación física (opcional para farmacias pequeñas)
            $table->string('ubicacion_bodega', 100)->nullable();     // Estante, anaquel, etc.

            // Fechas
            $table->date('fecha_ingreso')->useCurrent();
            $table->date('fecha_alerta_vencimiento')->nullable();    // Para alertas personalizadas

            // Notas
            $table->text('notas')->nullable();

            // Metadatos
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('producto_id');
            $table->index('numero_lote');
            $table->index('fecha_vencimiento');
            $table->index('estado');
            $table->index(['producto_id', 'estado']);                // Para buscar lotes disponibles
            $table->index(['producto_id', 'fecha_vencimiento']);     // Para FEFO
            $table->unique(['producto_id', 'numero_lote']);          // Un número de lote por producto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
