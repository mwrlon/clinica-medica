<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tokens do fluxo "Esqueci minha senha". Pacientes e médicos ficam em tabelas
// diferentes, então o token é identificado por e-mail + tipo de conta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 150);
            $table->enum('tipo', ['paciente', 'medico']);
            $table->string('token');
            $table->timestamp('created_at')->nullable();

            $table->primary(['email', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
