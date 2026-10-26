<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exame extends Model
{
    // A tabela usa "solicitado_em" / "disponibilizado_em" (sem created_at/updated_at)
    public $timestamps = false;

    protected $table = 'exames';

    protected $fillable = [
        'atendimento_id', 'paciente_id', 'nome', 'status',
        'resultado', 'arquivo_resultado', 'solicitado_em', 'disponibilizado_em',
    ];

    protected $casts = [
        'solicitado_em'      => 'datetime',
        'disponibilizado_em' => 'datetime',
    ];

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class, 'atendimento_id');
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
