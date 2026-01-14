<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Migración obsoleta: Sessions no se usa con Sanctum API tokens.
     * Se mantiene vacía para no romper el historial.
     */
    public function up(): void
    {
        // No-op: Ver migración 2026_01_08_000006
    }

    public function down(): void
    {
        // No-op
    }
};
