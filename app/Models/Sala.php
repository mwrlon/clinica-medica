<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sala extends Model
{
    // A tabela não possui colunas de data
    public $timestamps = false;

    protected $table = 'salas';

    protected $fillable = ['identificacao'];

    public function consultas(): HasMany
    {
        return $this->hasMany(Consulta::class, 'sala_id');
    }
}
