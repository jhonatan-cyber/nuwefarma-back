<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'usuario_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropColumn('usuario_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sessions') && ! Schema::hasColumn('sessions', 'usuario_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->foreignUuid('usuario_id')->nullable()->index()->after('id');
            });
        }
    }
};
