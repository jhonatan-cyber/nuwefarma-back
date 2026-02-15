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
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('pagado', 15, 2)->default(0)->after('total');
            $table->decimal('saldo_pendiente', 15, 2)->default(0)->after('pagado');
            $table->enum('tipo_pago', ['contado', 'credito'])->default('contado')->after('metodo_pago');
            $table->text('observaciones')->nullable()->after('notas');
            $table->text('motivo_cancelacion')->nullable()->after('observaciones');
            $table->timestamp('fecha_cancelacion')->nullable()->after('motivo_cancelacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['pagado', 'saldo_pendiente', 'tipo_pago', 'observaciones', 'motivo_cancelacion', 'fecha_cancelacion']);
        });
    }
};
