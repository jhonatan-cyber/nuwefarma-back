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
        $tables = [
            'categorias',
            'cajas',
            'compras',
            'clientes',
            'proveedores',
            'sucursals',
        ];

        foreach ($tables as $tableName) {
            if (! Schema::hasColumn($tableName, 'crear_usuario_id') || ! Schema::hasColumn($tableName, 'actualizar_usuario_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (! Schema::hasColumn($tableName, 'crear_usuario_id')) {
                        $table->uuid('crear_usuario_id')->nullable()->after('estado');
                    }

                    if (! Schema::hasColumn($tableName, 'actualizar_usuario_id')) {
                        $table->uuid('actualizar_usuario_id')->nullable()->after('crear_usuario_id');
                    }

                    $table->index('crear_usuario_id');
                    $table->index('actualizar_usuario_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'categorias',
            'cajas',
            'compras',
            'clientes',
            'proveedores',
            'sucursals',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasColumn($tableName, 'actualizar_usuario_id') || Schema::hasColumn($tableName, 'crear_usuario_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    if (Schema::hasColumn($table->getTable(), 'actualizar_usuario_id')) {
                        $table->dropIndex(['actualizar_usuario_id']);
                        $table->dropColumn('actualizar_usuario_id');
                    }

                    if (Schema::hasColumn($table->getTable(), 'crear_usuario_id')) {
                        $table->dropIndex(['crear_usuario_id']);
                        $table->dropColumn('crear_usuario_id');
                    }
                });
            }
        }
    }
};
