<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Medico extends Authenticatable
{
    use HasApiTokens, Notifiable;

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
}