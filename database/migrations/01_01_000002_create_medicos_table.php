<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('crm', 20)->unique('uq_medicos_crm')->comment('UF + número, ex.: RS-123456');
            $table->foreignId('especialidade_id')->constrained('especialidades')->cascadeOnDelete();
            $table->string('telefone', 20);
            $table->string('email', 150)->unique('uq_medicos_email')->comment('Também é o login');
            $table->string('senha_hash', 255)->comment('bcrypt');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            $table->timestamp('criado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};