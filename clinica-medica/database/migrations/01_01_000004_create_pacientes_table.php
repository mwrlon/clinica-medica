<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150)->index('idx_pacientes_nome');
            $table->string('cpf', 14)->unique('uq_pacientes_cpf');
            $table->date('data_nascimento');
            $table->enum('sexo', ['M', 'F', 'Outro']);
            $table->string('telefone', 20);
            $table->string('email', 150)->unique('uq_pacientes_email')->comment('Também é o login');
            $table->string('senha_hash', 255)->comment('bcrypt');
            $table->string('endereco', 255)->nullable();
            $table->foreignId('convenio_id')->nullable()->comment('NULL = particular')->constrained('convenios')->nullOnDelete();
            $table->timestamp('criado_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};