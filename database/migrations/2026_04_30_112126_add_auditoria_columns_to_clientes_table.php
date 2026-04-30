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
        Schema::table('clientes', function (Blueprint $table) {
            $table->uuid('crear_usuario_id')->nullable()->after('estado');
            $table->uuid('actualizar_usuario_id')->nullable()->after('crear_usuario_id');
            
            $table->foreign('crear_usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->foreign('actualizar_usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['crear_usuario_id']);
            $table->dropForeign(['actualizar_usuario_id']);
            $table->dropColumn(['crear_usuario_id', 'actualizar_usuario_id']);
        });
    }
};
