<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Administrador extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // A tabela só possui "criado_em" (sem updated_at)
    const CREATED_AT = 'criado_em';
    const UPDATED_AT = null;

    protected $table = 'administradores';

    protected $fillable = [
        'nome', 'email', 'senha_hash',
    ];

    protected $hidden = [
        'senha_hash',
    ];

    // Indica ao Laravel qual campo guarda a senha criptografada
    public function getAuthPassword()
    {
        return $this->senha_hash;
    }
}
