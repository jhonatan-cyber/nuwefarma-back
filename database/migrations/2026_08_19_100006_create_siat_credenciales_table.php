<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Credenciales cifradas del SIAT (usuario, token, certificado).
     * Nunca se guardan en texto plano: se cifran con la APP_KEY.
     */
    public function up(): void
    {
        Schema::create('siat_credenciales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre')->unique(); // ej: "token_pruebas", "firma_digital"
            $table->text('valor_cifrado');     // Crypt::encryptString()
            $table->enum('ambiente', ['pruebas', 'produccion'])->default('pruebas');
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siat_credenciales');
    }
};