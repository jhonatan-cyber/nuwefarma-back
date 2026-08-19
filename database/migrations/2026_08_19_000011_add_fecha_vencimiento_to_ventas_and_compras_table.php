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
            $table->date('fecha_vencimiento')->nullable()->after('fecha_venta');
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->date('fecha_vencimiento')->nullable()->after('fecha_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['fecha_vencimiento']);
        });

        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['fecha_vencimiento']);
        });
    }
};