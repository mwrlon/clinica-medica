<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Convenio extends Model
{
    // A tabela não possui colunas de data
    public $timestamps = false;

    protected $table = 'convenios';

    protected $fillable = ['nome'];

    public function pacientes(): HasMany
    {
        return $this->hasMany(Paciente::class, 'convenio_id');
    }
}
