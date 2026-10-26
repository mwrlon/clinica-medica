<?php

namespace Tests\Feature;

use App\Models\Consulta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class ConsultasTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private function payload($medico, $sala, array $extra = []): array
    {
        return array_merge([
            'medico_id' => $medico->id,
            'sala_id'   => $sala->id,
            'data'      => $this->amanha(2),
            'hora'      => '09:30',
        ], $extra);
    }

    public function test_paciente_agenda_consulta_para_si_mesmo(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        Sanctum::actingAs($paciente);

        $this->postJson('/api/consultas', $this->payload($medico, $this->sala()))
            ->assertCreated()
            ->assertJsonPath('consulta.paciente_id', $paciente->id)
            ->assertJsonPath('consulta.status', 'agendada');

        // "09:30" no SQLite e "09:30:00" no MySQL: compara só o começo
        $this->assertTrue(
            Consulta::where('medico_id', $medico->id)->where('paciente_id', $paciente->id)->where('hora', 'like', '09:30%')->exists()
        );
    }

    public function test_paciente_nao_consegue_agendar_em_nome_de_outro_paciente(): void
    {
        $medico = $this->medico();
        $eu = $this->paciente();
        $outro = $this->paciente();
        Sanctum::actingAs($eu);

        $this->postJson('/api/consultas', $this->payload($medico, $this->sala(), ['paciente_id' => $outro->id]))
            ->assertCreated()
            ->assertJsonPath('consulta.paciente_id', $eu->id);

        $this->assertDatabaseMissing('consultas', ['paciente_id' => $outro->id]);
    }

    public function test_medico_so_agenda_na_propria_agenda(): void
    {
        $eu = $this->medico();
        $outro = $this->medico();
        $paciente = $this->paciente();
        Sanctum::actingAs($eu);

        $this->postJson('/api/consultas', $this->payload($outro, $this->sala(), ['paciente_id' => $paciente->id]))
            ->assertCreated()
            ->assertJsonPath('consulta.medico_id', $eu->id);
    }

    public function test_horario_ocupado_do_medico_e_recusado(): void
    {
        $medico = $this->medico();
        $this->consulta($medico, $this->paciente(), ['data' => $this->amanha(2), 'hora' => '09:30']);

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', $this->payload($medico, $this->sala()))
            ->assertStatus(422)
            ->assertJsonPath('mensagem', 'O médico já possui uma consulta agendada para este dia e horário.');
    }

    public function test_paciente_nao_pode_ter_duas_consultas_no_mesmo_horario(): void
    {
        $paciente = $this->paciente();
        $this->consulta($this->medico(), $paciente, ['data' => $this->amanha(2), 'hora' => '09:30']);

        Sanctum::actingAs($paciente);
        $this->postJson('/api/consultas', $this->payload($this->medico(), $this->sala()))
            ->assertStatus(422)
            ->assertJsonPath('mensagem', 'O paciente já possui uma consulta agendada para este mesmo horário.');
    }

    public function test_nao_agenda_no_passado_nem_com_medico_inativo(): void
    {
        Sanctum::actingAs($this->paciente());
        $sala = $this->sala();

        $this->postJson('/api/consultas', $this->payload($this->medico(), $sala, ['data' => now()->subDay()->toDateString()]))
            ->assertStatus(422)->assertJsonValidationErrors(['data']);

        $this->postJson('/api/consultas', $this->payload($this->medico(['status' => 'inativo']), $sala))
            ->assertStatus(422)->assertJsonValidationErrors(['medico_id']);
    }

    public function test_cancelar_libera_o_horario_para_novo_agendamento(): void
    {
        // Regressão: antes, o índice único ainda contava a consulta cancelada e o novo agendamento dava erro 500
        $medico = $this->medico();
        $paciente = $this->paciente();
        $sala = $this->sala();
        Sanctum::actingAs($paciente);

        $id = $this->postJson('/api/consultas', $this->payload($medico, $sala))->assertCreated()->json('consulta.id');

        $this->putJson("/api/consultas/{$id}/cancelar")->assertOk();
        $this->assertSame('cancelada', Consulta::find($id)->status);

        $this->postJson('/api/consultas', $this->payload($medico, $sala))->assertCreated();

        // Outro paciente também pode ocupar o horário que ficou livre
        $this->postJson('/api/consultas', $this->payload($medico, $sala))->assertStatus(422);
    }

    public function test_so_o_dono_ou_o_medico_da_consulta_pode_cancelar(): void
    {
        $medico = $this->medico();
        $dono = $this->paciente();
        $consulta = $this->consulta($medico, $dono);

        Sanctum::actingAs($this->paciente());
        $this->putJson("/api/consultas/{$consulta->id}/cancelar")->assertStatus(403);

        Sanctum::actingAs($this->medico());
        $this->putJson("/api/consultas/{$consulta->id}/cancelar")->assertStatus(403);

        $this->assertSame('agendada', $consulta->fresh()->status);

        Sanctum::actingAs($medico);
        $this->putJson("/api/consultas/{$consulta->id}/cancelar")->assertOk();
    }

    public function test_consulta_realizada_nao_pode_ser_cancelada(): void
    {
        $paciente = $this->paciente();
        $consulta = $this->consulta($this->medico(), $paciente, ['status' => 'realizada']);

        Sanctum::actingAs($paciente);
        $this->putJson("/api/consultas/{$consulta->id}/cancelar")->assertStatus(422);
    }

    public function test_cancelar_consulta_inexistente_responde_404_em_json(): void
    {
        Sanctum::actingAs($this->paciente());
        $this->putJson('/api/consultas/9999/cancelar')->assertNotFound();
    }

    public function test_listagem_mostra_so_as_consultas_de_quem_esta_logado_em_ordem_cronologica(): void
    {
        $medico = $this->medico();
        $helena = $this->paciente();
        $outra = $this->paciente();

        $this->consulta($medico, $helena, ['data' => $this->amanha(5), 'hora' => '08:00']);
        $this->consulta($medico, $helena, ['data' => $this->amanha(2), 'hora' => '15:00']);
        $this->consulta($this->medico(), $outra, ['data' => $this->amanha(2), 'hora' => '09:00']);

        Sanctum::actingAs($helena);
        $resposta = $this->getJson('/api/consultas')->assertOk()->assertJsonCount(2);
        $this->assertSame([$this->amanha(2), $this->amanha(5)], array_column($resposta->json(), 'data'));
        $resposta->assertJsonStructure([['id', 'data', 'hora', 'status', 'medico' => ['nome'], 'sala']]);

        // O médico vê as dele; a resposta não vaza as colunas auxiliares dos índices
        Sanctum::actingAs($medico);
        $resposta = $this->getJson('/api/consultas')->assertOk()->assertJsonCount(2);
        $resposta->assertJsonMissingPath('0.medico_horario_ativo');
        $this->assertSame($helena->id, $resposta->json('0.paciente.id'));
    }

    public function test_filtro_por_data(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $this->consulta($medico, $paciente, ['data' => $this->amanha(2), 'hora' => '08:00']);
        $this->consulta($medico, $paciente, ['data' => $this->amanha(4), 'hora' => '08:00']);

        Sanctum::actingAs($medico);
        $this->getJson('/api/consultas?data=' . $this->amanha(4))->assertOk()->assertJsonCount(1);
    }
}
