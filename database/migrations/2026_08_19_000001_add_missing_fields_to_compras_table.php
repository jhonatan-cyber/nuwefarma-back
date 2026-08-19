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
        Schema::table('compras', function (Blueprint $table) {
            $table->uuid('caja_id')->nullable()->after('sucursal_id');
            $table->string('tipo_documento', 50)->nullable()->after('metodo_pago');
            $table->string('numero_documento', 100)->nullable()->after('tipo_documento');
            $table->date('fecha_documento')->nullable()->after('numero_documento');
            $table->decimal('pagado', 15, 2)->default(0)->after('total');
            $table->decimal('saldo_pendiente', 15, 2)->default(0)->after('pagado');
            $table->text('observaciones')->nullable()->after('notas');
            $table->text('motivo_cancelacion')->nullable()->after('observaciones');
            $table->timestamp('fecha_cancelacion')->nullable()->after('motivo_cancelacion');

            $table->foreign('caja_id')->references('id')->on('cajas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['caja_id']);
            $table->dropColumn([
                'caja_id',
                'tipo_documento',
                'numero_documento',
                'fecha_documento',
                'pagado',
                'saldo_pendiente',
                'observaciones',
                'motivo_cancelacion',
                'fecha_cancelacion',
            ]);
        });
    }
};
