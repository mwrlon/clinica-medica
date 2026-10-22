<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atendimento_id')->constrained('atendimentos')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->string('nome', 150)->comment('Ex.: Hemograma completo');
            $table->enum('status', ['solicitado', 'realizado', 'resultado_disponivel'])->default('solicitado');
            $table->text('resultado')->nullable();
            $table->string('arquivo_resultado', 255)->nullable()->comment('Caminho/URL do laudo (PDF, imagem)');
            $table->dateTime('solicitado_em')->useCurrent();
            $table->dateTime('disponibilizado_em')->nullable();

            $table->index(['paciente_id', 'status'], 'idx_exames_paciente_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exames');
    }
};