<?php

namespace Database\Seeders;

use App\Models\Atendimento;
use App\Models\Consulta;
use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Exame;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Sala;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados de demonstração (NÃO usar em produção): a equipe médica exibida no site,
 * a paciente dos painéis e algumas consultas, atendimentos e exames.
 *
 * Logins de teste:
 *   médico   rafael.tavares@prosaude.com.br  /  medico123   (e os demais médicos, mesma senha)
 *   paciente helena@exemplo.com              /  paciente123
 */
class DemoSeeder extends Seeder
{
    private const SENHA_MEDICO   = 'medico123';
    private const SENHA_PACIENTE = 'paciente123';

    public function run(): void
    {
        $medicos = $this->criarMedicos();
        $sala    = Sala::where('identificacao', 'Consultório 01')->first();
        $hoje    = now()->startOfDay();

        $helena = $this->paciente('Helena Marques', '123.456.789-00', $hoje->copy()->subYears(41), 'F', 'helena@exemplo.com', 'Unimed');
        $ana    = $this->paciente('Ana Clara Souza', '000.000.000-01', $hoje->copy()->subYears(7), 'F', 'ana.souza@exemplo.com');
        $gabriel = $this->paciente('Gabriel Santos', '000.000.000-02', $hoje->copy()->subYears(12), 'M', 'gabriel.santos@exemplo.com');
        $lucas  = $this->paciente('Lucas Silva', '000.000.000-03', $hoje->copy()->subYears(8), 'M', 'lucas.silva@exemplo.com');
        $mariana = $this->paciente('Mariana Oliveira', '000.000.000-04', $hoje->copy()->subYears(15), 'F', 'mariana.oliveira@exemplo.com');
        $enzo   = $this->paciente('Enzo Gabriel Ferraz', '000.000.000-05', $hoje->copy()->subYears(3), 'M', 'enzo.ferraz@exemplo.com');

        $rafael = $medicos['rafael.tavares@prosaude.com.br'];
        $marina = $medicos['marina.ferraz@prosaude.com.br'];

        // Agenda de hoje do Dr. Rafael Tavares (pediatra)
        $c1 = $this->consulta($rafael, $ana, $sala, $hoje, '08:30:00', 'realizada', 'Retorno de exames');
        $c2 = $this->consulta($rafael, $gabriel, $sala, $hoje, '09:30:00', 'realizada', 'Primeira consulta');
        $this->consulta($rafael, $lucas, $sala, $hoje, '14:00:00', 'agendada', 'Acompanhamento pediátrico');
        $this->consulta($rafael, $mariana, $sala, $hoje, '15:00:00', 'agendada', 'Avaliação geral');
        $this->consulta($rafael, $enzo, $sala, $hoje, '16:00:00', 'agendada', 'Vacinação e peso');

        $this->atendimento($c1, 'Recuperação dentro do esperado.', null);
        $this->atendimento($c2, 'Criança saudável, crescimento adequado.', "Vitamina D 400UI — 1 gota ao dia.");

        // Histórico e próxima consulta da Helena Marques
        $passadaRafael = $this->consulta($rafael, $helena, $sala, $hoje->copy()->subDays(25), '10:00:00', 'realizada', 'Check-up');
        $passadaMarina = $this->consulta($marina, $helena, $sala, $hoje->copy()->subDays(75), '09:00:00', 'realizada', 'Avaliação cardiológica');
        $this->consulta($marina, $helena, $sala, $hoje->copy()->addDays(10), '10:30:00', 'agendada', 'Retorno cardiológico');

        $atRafael = $this->atendimento(
            $passadaRafael,
            'Gastrite leve.',
            "Omeprazol 20mg — 1 comprimido às 08:00 (jejum).\nVitamina D 2000UI — 1 cápsula no almoço."
        );
        $atMarina = $this->atendimento($passadaMarina, 'Exame físico normal.', null);

        $this->exame($atRafael, $helena, 'Hemograma completo', 'resultado_disponivel', $passadaRafael->data->copy()->addDay(), 'Valores dentro da normalidade.');
        $this->exame($atMarina, $helena, 'Eletrocardiograma', 'realizado', $passadaMarina->data->copy(), null);
    }

    /** @return array<string, Medico> indexado pelo e-mail */
    private function criarMedicos(): array
    {
        $equipe = [
            ['Dra. Marina Ferraz', 'RS-38211', 'Cardiologia', 'marina.ferraz@prosaude.com.br'],
            ['Dr. Rafael Tavares', 'RS-45678', 'Pediatria', 'rafael.tavares@prosaude.com.br'],
            ['Dra. Camila Lopes', 'RS-41907', 'Dermatologia', 'camila.lopes@prosaude.com.br'],
            ['Dr. João Salles', 'RS-29854', 'Ortopedia', 'joao.salles@prosaude.com.br'],
            ['Dra. Helena Prado', 'RS-50233', 'Ginecologia', 'helena.prado@prosaude.com.br'],
        ];

        $medicos = [];

        foreach ($equipe as [$nome, $crm, $especialidade, $email]) {
            $medicos[$email] = Medico::firstOrCreate(
                ['email' => $email],
                [
                    'nome'             => $nome,
                    'crm'              => $crm,
                    'especialidade_id' => Especialidade::where('nome', $especialidade)->value('id'),
                    'telefone'         => '(51) 99999-9999',
                    'senha_hash'       => Hash::make(self::SENHA_MEDICO),
                    'status'           => 'ativo',
                ]
            );
        }

        return $medicos;
    }

    private function paciente(string $nome, string $cpf, $nascimento, string $sexo, string $email, ?string $convenio = null): Paciente
    {
        return Paciente::firstOrCreate(
            ['email' => $email],
            [
                'nome'            => $nome,
                'cpf'             => $cpf,
                'data_nascimento' => $nascimento->toDateString(),
                'sexo'            => $sexo,
                'telefone'        => '(51) 98888-7777',
                'senha_hash'      => Hash::make(self::SENHA_PACIENTE),
                'convenio_id'     => $convenio ? Convenio::where('nome', $convenio)->value('id') : null,
            ]
        );
    }

    private function consulta(Medico $medico, Paciente $paciente, Sala $sala, $data, string $hora, string $status, string $motivo): Consulta
    {
        return Consulta::firstOrCreate(
            ['medico_id' => $medico->id, 'data' => $data->toDateString(), 'hora' => $hora],
            ['paciente_id' => $paciente->id, 'sala_id' => $sala->id, 'status' => $status, 'observacoes' => $motivo]
        );
    }

    private function atendimento(Consulta $consulta, string $diagnostico, ?string $receita): Atendimento
    {
        return Atendimento::firstOrCreate(
            ['consulta_id' => $consulta->id],
            ['diagnostico' => $diagnostico, 'receita' => $receita]
        );
    }

    private function exame(Atendimento $atendimento, Paciente $paciente, string $nome, string $status, $data, ?string $resultado): Exame
    {
        return Exame::firstOrCreate(
            ['atendimento_id' => $atendimento->id, 'nome' => $nome],
            [
                'paciente_id'        => $paciente->id,
                'status'             => $status,
                'resultado'          => $resultado,
                'solicitado_em'      => $data,
                'disponibilizado_em' => $status === 'resultado_disponivel' ? $data : null,
            ]
        );
    }
}
