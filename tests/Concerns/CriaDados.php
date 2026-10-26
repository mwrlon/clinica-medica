<?php

namespace Tests\Concerns;

use App\Models\Administrador;
use App\Models\Consulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Sala;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Atalhos para montar dados nos testes (as senhas padrão são "medico123", "paciente123" e "admin12345").
 */
trait CriaDados
{
    private int $sequencia = 0;

    protected function proximo(): int
    {
        return ++$this->sequencia;
    }

    protected function especialidadeId(string $nome = 'Cardiologia'): int
    {
        DB::table('especialidades')->insertOrIgnore(['nome' => $nome]);

        return (int) DB::table('especialidades')->where('nome', $nome)->value('id');
    }

    protected function sala(string $identificacao = 'Consultório 01'): Sala
    {
        return Sala::firstOrCreate(['identificacao' => $identificacao]);
    }

    protected function medico(array $atributos = []): Medico
    {
        $n = $this->proximo();

        return Medico::create(array_merge([
            'nome'             => "Dr. Teste {$n}",
            'crm'              => "RS-{$n}0000",
            'especialidade_id' => $this->especialidadeId(),
            'telefone'         => '(54) 99999-0000',
            'email'            => "medico{$n}@teste.com",
            'senha_hash'       => Hash::make('medico123'),
            'status'           => 'ativo',
        ], $atributos));
    }

    protected function paciente(array $atributos = []): Paciente
    {
        $n = $this->proximo();

        return Paciente::create(array_merge([
            'nome'            => "Paciente Teste {$n}",
            'cpf'             => sprintf('%03d.000.000-00', $n),
            'data_nascimento' => '1990-05-10',
            'sexo'            => 'F',
            'telefone'        => '(54) 98888-0000',
            'email'           => "paciente{$n}@teste.com",
            'senha_hash'      => Hash::make('paciente123'),
        ], $atributos));
    }

    protected function administrador(array $atributos = []): Administrador
    {
        return Administrador::create(array_merge([
            'nome'       => 'Administrador Teste',
            'email'      => 'admin@teste.com',
            'senha_hash' => Hash::make('admin12345'),
        ], $atributos));
    }

    protected function consulta(Medico $medico, Paciente $paciente, array $atributos = []): Consulta
    {
        return Consulta::create(array_merge([
            'medico_id'   => $medico->id,
            'paciente_id' => $paciente->id,
            'sala_id'     => $this->sala()->id,
            'data'        => now()->addDays(3)->toDateString(),
            'hora'        => '10:00',
            'status'      => 'agendada',
        ], $atributos));
    }

    protected function amanha(int $dias = 1): string
    {
        return now()->addDays($dias)->toDateString();
    }
}
