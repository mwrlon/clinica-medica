<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Paciente extends Authenticatable
{
    use HasApiTokens, Notifiable;

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
}