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
        Schema::table('sucursals', function (Blueprint $table) {
            // Eliminar codigo_postal
            $table->dropColumn('codigo_postal');
            
            // Renombrar departamento a pais
            $table->renameColumn('departamento', 'pais');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sucursals', function (Blueprint $table) {
            // Restaurar codigo_postal
            $table->string('codigo_postal')->nullable()->after('ciudad');
            
            // Renombrar pais a departamento
            $table->renameColumn('pais', 'departamento');
        });
    }
};
