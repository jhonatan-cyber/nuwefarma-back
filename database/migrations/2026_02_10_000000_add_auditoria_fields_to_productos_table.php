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
        Schema::table('productos', function (Blueprint $table) {
            $table->uuid('crear_usuario_id')->nullable()->after('estado');
            $table->uuid('actualizar_usuario_id')->nullable()->after('crear_usuario_id');
            
            $table->foreign('crear_usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->foreign('actualizar_usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            
            $table->index('crear_usuario_id');
            $table->index('actualizar_usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropForeign(['crear_usuario_id']);
            $table->dropForeign(['actualizar_usuario_id']);
            $table->dropIndex(['crear_usuario_id']);
            $table->dropIndex(['actualizar_usuario_id']);
            $table->dropColumn(['crear_usuario_id', 'actualizar_usuario_id']);
        });
    }
};
