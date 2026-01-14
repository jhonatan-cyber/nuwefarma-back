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
        Schema::table('gastos', function (Blueprint $table) {
            // Eliminar la foreign key
            $table->dropForeign(['categoria_id']);
            // Eliminar la columna categoria_id
            $table->dropColumn('categoria_id');
            // Agregar columna categoria como string
            $table->string('categoria')->after('monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropColumn('categoria');
            $table->foreignUuid('categoria_id')->constrained('categorias')->onDelete('cascade');
        });
    }
};
