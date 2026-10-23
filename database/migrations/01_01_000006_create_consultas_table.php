<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('sala_id')->constrained('salas')->cascadeOnDelete();
            $table->date('data');
            $table->time('hora');
            $table->text('observacoes')->nullable();
            $table->enum('status', ['agendada', 'realizada', 'cancelada'])->default('agendada');
            $table->timestamp('criado_em')->useCurrent();

            // Índices compostos únicos
            $table->unique(['medico_id', 'data', 'hora'], 'uq_consultas_medico_horario');
            $table->unique(['paciente_id', 'data', 'hora'], 'uq_consultas_paciente_horario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};