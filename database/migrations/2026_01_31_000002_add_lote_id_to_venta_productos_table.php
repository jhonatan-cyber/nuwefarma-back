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
        Schema::table('venta_productos', function (Blueprint $table) {
            $table->uuid('lote_id')->nullable()->after('producto_id');
            $table->foreign('lote_id')->references('id')->on('lotes')->onDelete('set null');
            $table->index(['lote_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venta_productos', function (Blueprint $table) {
            $table->dropForeign(['lote_id']);
            $table->dropIndex(['lote_id']);
            $table->dropColumn('lote_id');
        });
    }
};
