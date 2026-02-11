<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tipo', 50);
            $table->string('titulo', 255);
            $table->text('mensaje');
            $table->string('modulo', 50);
            $table->string('registro_id', 36)->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->uuid('usuario_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
            $table->index(['tipo']);
            $table->index(['estado']);
            $table->index(['usuario_id', 'estado']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
