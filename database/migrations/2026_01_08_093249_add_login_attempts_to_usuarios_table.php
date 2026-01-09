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
        Schema::table('usuarios', function (Blueprint $table) {
            $table->integer('intentos_fallidos')->default(0)->after('estado');
            $table->timestamp('bloqueado_hasta')->nullable()->after('intentos_fallidos');
            $table->timestamp('ultimo_intento_fallido')->nullable()->after('bloqueado_hasta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['intentos_fallidos', 'bloqueado_hasta', 'ultimo_intento_fallido']);
        });
    }
};
