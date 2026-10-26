<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidade extends Model
{
    // A tabela não possui colunas de data
    public $timestamps = false;

    protected $table = 'especialidades';

    protected $fillable = ['nome'];

    public function medicos(): HasMany
    {
        return $this->hasMany(Medico::class, 'especialidade_id');
    }
}
