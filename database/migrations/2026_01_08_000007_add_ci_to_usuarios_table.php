<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuarios') && ! Schema::hasColumn('usuarios', 'ci')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('ci')->unique()->after('apellidos');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('usuarios') && Schema::hasColumn('usuarios', 'ci')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('ci');
            });
        }
    }
};
