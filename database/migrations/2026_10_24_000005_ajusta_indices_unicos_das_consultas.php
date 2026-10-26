<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consultas canceladas não podem bloquear o horário.
 *
 * Os índices únicos originais (medico_id, data, hora) e (paciente_id, data, hora) também
 * valiam para consultas canceladas: cancelar e voltar a marcar o mesmo horário falhava com
 * "Duplicate entry". A regra do sistema é que cancelada libera o horário.
 *
 * Solução portátil (MySQL e SQLite, que não têm índice parcial): colunas geradas que valem
 * NULL quando a consulta está cancelada (NULLs não colidem em índice único) e os índices
 * únicos passam a usá-las no lugar de medico_id/paciente_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->unsignedBigInteger('medico_horario_ativo')->nullable()
                ->virtualAs("CASE WHEN status = 'cancelada' THEN NULL ELSE medico_id END");
            $table->unsignedBigInteger('paciente_horario_ativo')->nullable()
                ->virtualAs("CASE WHEN status = 'cancelada' THEN NULL ELSE paciente_id END");
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->unique(['medico_horario_ativo', 'data', 'hora'], 'uq_consultas_medico_horario_ativo');
            $table->unique(['paciente_horario_ativo', 'data', 'hora'], 'uq_consultas_paciente_horario_ativo');

            // No MySQL as chaves estrangeiras de medico_id/paciente_id usavam os índices únicos
            // antigos como apoio; precisam de um índice próprio antes de eles serem removidos.
            $table->index('medico_id', 'idx_consultas_medico');
            $table->index('paciente_id', 'idx_consultas_paciente');
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropUnique('uq_consultas_medico_horario');
            $table->dropUnique('uq_consultas_paciente_horario');
        });
    }

    public function down(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropUnique('uq_consultas_medico_horario_ativo');
            $table->dropUnique('uq_consultas_paciente_horario_ativo');
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropColumn(['medico_horario_ativo', 'paciente_horario_ativo']);
            $table->unique(['medico_id', 'data', 'hora'], 'uq_consultas_medico_horario');
            $table->unique(['paciente_id', 'data', 'hora'], 'uq_consultas_paciente_horario');
        });

        Schema::table('consultas', function (Blueprint $table) {
            $table->dropIndex('idx_consultas_medico');
            $table->dropIndex('idx_consultas_paciente');
        });
    }
};
