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
            // Eliminar columna gerente antigua
            $table->dropColumn('gerente');
            
            // Agregar nueva columna gerente_id con foreign key
            $table->foreignUuid('gerente_id')
                ->nullable()
                ->after('email')
                ->constrained('usuarios')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sucursals', function (Blueprint $table) {
            // Eliminar foreign key y columna gerente_id
            $table->dropForeign(['gerente_id']);
            $table->dropColumn('gerente_id');
            
            // Restaurar columna gerente antigua
            $table->string('gerente')->nullable()->after('email');
        });
    }
};
