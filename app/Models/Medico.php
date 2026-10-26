<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Medico extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // A tabela só possui "criado_em" (sem updated_at)
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $table = 'medicos';

    protected $fillable = [
        'nome', 'crm', 'especialidade_id', 'telefone', 'email', 'senha_hash', 'status'
    ];

    protected $hidden = [
        'senha_hash',
    ];

    // Indica ao Laravel qual campo guarda a senha criptografada
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }

    public function especialidade(): BelongsTo
    {
        return $this->belongsTo(Especialidade::class, 'especialidade_id');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'medico_id');
    }
}
