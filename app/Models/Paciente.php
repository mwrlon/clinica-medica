<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Paciente extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // A tabela só possui "criado_em" (sem updated_at)
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $table = 'pacientes';

    protected $fillable = [
        'nome', 'cpf', 'data_nascimento', 'sexo',
        'telefone', 'email', 'senha_hash', 'endereco', 'convenio_id'
    ];

    protected $hidden = [
        'senha_hash',
    ];

    // Indica ao Laravel qual campo guarda a senha criptografada
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(Convenio::class, 'convenio_id');
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'paciente_id');
    }

    public function exames(): HasMany
    {
        return $this->hasMany(Exame::class, 'paciente_id');
    }
}
