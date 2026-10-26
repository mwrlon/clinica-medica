<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atendimento extends Model
{
    // A tabela só possui "criado_em" (sem updated_at)
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $table = 'atendimentos';

    protected $fillable = [
        'consulta_id', 'diagnostico', 'receita', 'observacoes',
    ];

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    public function exames(): HasMany
    {
        return $this->hasMany(Exame::class, 'atendimento_id');
    }
}
