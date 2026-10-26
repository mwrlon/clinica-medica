<?php

namespace Tests\Feature;

use App\Models\Atendimento;
use App\Models\Exame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class AtendimentosEExamesTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    public function test_medico_registra_atendimento_pede_exames_e_a_consulta_vira_realizada(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consulta($medico, $paciente);
        Sanctum::actingAs($medico);

        $this->postJson('/api/atendimentos', [
            'consulta_id' => $consulta->id,
            'diagnostico' => 'Rinite alérgica',
            'receita'     => "Loratadina 10mg — 1 comprimido ao dia",
            'exames'      => [['nome' => 'Hemograma completo'], ['nome' => 'Raio-X de tórax']],
        ])->assertCreated()->assertJsonCount(2, 'atendimento.exames');

        $this->assertSame('realizada', $consulta->fresh()->status);
        $this->assertDatabaseHas('exames', ['paciente_id' => $paciente->id, 'nome' => 'Hemograma completo', 'status' => 'solicitado']);
    }

    public function test_paciente_nao_pode_registrar_atendimento(): void
    {
        $paciente = $this->paciente();
        $consulta = $this->consulta($this->medico(), $paciente);
        Sanctum::actingAs($paciente);

        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id, 'diagnostico' => 'x'])->assertStatus(403);
        $this->assertSame(0, Atendimento::count());
    }

    public function test_medico_nao_registra_atendimento_de_consulta_de_outro_medico(): void
    {
        $consulta = $this->consulta($this->medico(), $this->paciente());
        Sanctum::actingAs($this->medico());

        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id, 'diagnostico' => 'x'])->assertStatus(403);
        $this->assertSame('agendada', $consulta->fresh()->status);
    }

    public function test_nao_registra_atendimento_duplicado_nem_de_consulta_cancelada(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consulta($medico, $paciente, ['hora' => '08:00']);
        $cancelada = $this->consulta($medico, $paciente, ['hora' => '09:00', 'status' => 'cancelada']);
        Sanctum::actingAs($medico);

        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id, 'diagnostico' => 'ok'])->assertCreated();
        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id, 'diagnostico' => 'de novo'])->assertStatus(422);
        $this->postJson('/api/atendimentos', ['consulta_id' => $cancelada->id, 'diagnostico' => 'x'])->assertStatus(422);
    }

    public function test_diagnostico_e_obrigatorio(): void
    {
        $medico = $this->medico();
        $consulta = $this->consulta($medico, $this->paciente());
        Sanctum::actingAs($medico);

        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id])
            ->assertStatus(422)->assertJsonValidationErrors(['diagnostico']);
    }

    public function test_listagem_de_consultas_traz_a_receita_do_atendimento(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consulta($medico, $paciente, ['status' => 'realizada']);
        Atendimento::create(['consulta_id' => $consulta->id, 'diagnostico' => 'ok', 'receita' => 'Omeprazol 20mg']);

        Sanctum::actingAs($paciente);
        $this->getJson('/api/consultas')->assertOk()->assertJsonPath('0.atendimento.receita', 'Omeprazol 20mg');
    }

    public function test_exames_cada_perfil_ve_so_os_seus(): void
    {
        $medico = $this->medico();
        $outroMedico = $this->medico();
        $helena = $this->paciente();
        $outra = $this->paciente();

        $mkExame = function ($m, $p, $nome) {
            $c = $this->consulta($m, $p, ['hora' => sprintf('%02d:00', 8 + $this->proximo())]);
            $a = Atendimento::create(['consulta_id' => $c->id, 'diagnostico' => 'x']);
            return Exame::create(['atendimento_id' => $a->id, 'paciente_id' => $p->id, 'nome' => $nome, 'status' => 'solicitado', 'solicitado_em' => now()]);
        };

        $mkExame($medico, $helena, 'Hemograma completo');
        $mkExame($outroMedico, $outra, 'Ultrassom');

        Sanctum::actingAs($helena);
        $this->getJson('/api/exames')->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Hemograma completo');

        Sanctum::actingAs($medico);
        $this->getJson('/api/exames')->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Hemograma completo');

        Sanctum::actingAs($this->administrador());
        $this->getJson('/api/exames')->assertOk()->assertJsonCount(2);
    }
}
