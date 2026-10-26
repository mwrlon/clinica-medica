<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consulta extends Model
{
    // A tabela só possui "criado_em" (sem updated_at)
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $table = 'consultas';

    protected $fillable = [
        'medico_id', 'paciente_id', 'sala_id', 'data', 'hora', 'observacoes', 'status',
    ];

    protected $casts = [
        // Serializa como "AAAA-MM-DD" (sem fuso horário) para o front-end
        'data' => 'date:Y-m-d',
    ];

    // Colunas geradas que só existem para o índice único (consultas canceladas não bloqueiam horário)
    protected $hidden = [
        'medico_horario_ativo', 'paciente_horario_ativo',
    ];

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function sala(): BelongsTo
    {
        return $this->belongsTo(Sala::class, 'sala_id');
    }

    public function atendimento(): HasOne
    {
        return $this->hasOne(Atendimento::class, 'consulta_id');
    }
}
