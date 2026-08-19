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
        Schema::table('compra_productos', function (Blueprint $table) {
            $table->string('numero_lote', 100)->nullable()->after('producto_id');
            $table->date('fecha_vencimiento')->nullable()->after('numero_lote');
            $table->integer('cantidad_recibida')->default(0)->after('cantidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compra_productos', function (Blueprint $table) {
            $table->dropColumn(['numero_lote', 'fecha_vencimiento', 'cantidad_recibida']);
        });
    }
};
