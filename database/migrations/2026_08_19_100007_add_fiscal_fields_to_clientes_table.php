<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos fiscales de clientes necesarios para la facturación SIAT
     * (NIT/CI del comprador y datos de contacto completos).
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nit', 30)->nullable()->after('ci');
            $table->string('email')->nullable()->after('telefono');
            $table->string('direccion')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['nit', 'email', 'direccion']);
        });
    }
};