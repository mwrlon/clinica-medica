<?php

namespace Tests\Feature;

use App\Models\Convenio;
use App\Models\Medico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * API do painel do administrador: visão geral, consultas com filtros, pacientes e as travas de exclusão.
 */
class PainelAdminTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private function entrarComoAdmin(): void
    {
        Sanctum::actingAs($this->administrador());
    }

    public function test_resumo_conta_medicos_pacientes_e_consultas(): void
    {
        $ativo = $this->medico();
        $this->medico(['status' => 'inativo']);
        $helena = $this->paciente();
        $this->paciente();

        // Horários da madrugada de hoje: contam em "hoje" e no mês, mas já passaram (não são "próximas")
        $this->consulta($ativo, $helena, ['data' => now()->toDateString(), 'hora' => '00:00']);
        $this->consulta($ativo, $helena, ['data' => now()->toDateString(), 'hora' => '00:30', 'status' => 'cancelada']);

        $this->entrarComoAdmin();

        $this->getJson('/api/admin/resumo')
            ->assertOk()
            ->assertJsonPath('medicos.ativos', 1)
            ->assertJsonPath('medicos.inativos', 1)
            ->assertJsonPath('pacientes.total', 2)
            ->assertJsonPath('pacientes.novos_30_dias', 2)
            ->assertJsonPath('consultas.hoje', 1)          // a cancelada não conta
            ->assertJsonPath('consultas.mes_canceladas', 1)
            ->assertJsonPath('por_especialidade.0.nome', 'Cardiologia')
            ->assertJsonPath('por_especialidade.0.total', 1)
            ->assertJsonPath('cadastros.salas', 1);
    }

    public function test_resumo_lista_as_proximas_consultas_agendadas_em_ordem(): void
    {
        $medico = $this->medico();
        $helena = $this->paciente(['nome' => 'Helena Prado']);
        $bruno = $this->paciente(['nome' => 'Bruno Reis']);

        $this->consulta($medico, $bruno, ['data' => $this->amanha(3), 'hora' => '09:00']);
        $this->consulta($medico, $helena, ['data' => $this->amanha(2), 'hora' => '15:00']);
        $this->consulta($medico, $helena, ['data' => $this->amanha(4), 'hora' => '09:00', 'status' => 'cancelada']);
        $this->consulta($medico, $helena, ['data' => now()->subDays(2)->toDateString(), 'hora' => '09:00']);

        $this->entrarComoAdmin();

        $this->getJson('/api/admin/resumo')
            ->assertOk()
            ->assertJsonCount(2, 'proximas')
            ->assertJsonPath('proximas.0.paciente.nome', 'Helena Prado')
            ->assertJsonPath('proximas.1.paciente.nome', 'Bruno Reis');
    }

    public function test_resumo_sem_dados_responde_zeros_e_listas_vazias(): void
    {
        $this->entrarComoAdmin();

        $this->getJson('/api/admin/resumo')
            ->assertOk()
            ->assertJsonPath('medicos.ativos', 0)
            ->assertJsonPath('consultas.hoje', 0)
            ->assertJsonPath('por_especialidade', [])
            ->assertJsonPath('proximas', []);
    }

    public function test_consultas_vem_paginadas_da_mais_recente_para_a_mais_antiga(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();

        foreach (range(1, 5) as $dia) {
            $this->consulta($medico, $paciente, ['data' => $this->amanha($dia)]);
        }

        $this->entrarComoAdmin();

        $resposta = $this->getJson('/api/admin/consultas?por_pagina=2')->assertOk();
        $resposta->assertJsonPath('total', 5)->assertJsonPath('last_page', 3)->assertJsonCount(2, 'data');
        $resposta->assertJsonPath('data.0.data', $this->amanha(5));
        $resposta->assertJsonStructure(['data' => [['id', 'data', 'hora', 'status', 'medico' => ['nome', 'especialidade'], 'paciente' => ['nome', 'cpf'], 'sala']]]);

        $this->getJson('/api/admin/consultas?por_pagina=2&page=3')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_consultas_filtra_por_status_periodo_medico_e_busca(): void
    {
        $medicoA = $this->medico(['nome' => 'Dr. Alberto']);
        $medicoB = $this->medico(['nome' => 'Dra. Beatriz']);
        $ana = $this->paciente(['nome' => 'Ana Lima', 'cpf' => '123.456.789-00']);
        $bruno = $this->paciente(['nome' => 'Bruno Reis']);

        $c1 = $this->consulta($medicoA, $ana, ['data' => $this->amanha(1), 'hora' => '09:00']);
        $c2 = $this->consulta($medicoA, $bruno, ['data' => $this->amanha(5), 'hora' => '09:00', 'status' => 'cancelada']);
        $c3 = $this->consulta($medicoB, $bruno, ['data' => $this->amanha(9), 'hora' => '09:00']);

        $this->entrarComoAdmin();

        $ids = fn (string $query) => collect($this->getJson("/api/admin/consultas?{$query}")->assertOk()->json('data'))
            ->pluck('id')->sort()->values()->all();

        $this->assertSame([$c2->id], $ids('status=cancelada'));
        $this->assertSame([$c1->id, $c2->id], $ids('medico_id=' . $medicoA->id));
        $this->assertSame([$c2->id, $c3->id], $ids('data_inicio=' . $this->amanha(5)));
        $this->assertSame([$c1->id, $c2->id], $ids('data_fim=' . $this->amanha(5)));
        $this->assertSame([$c1->id], $ids('busca=Ana'));
        $this->assertSame([$c1->id], $ids('busca=123.456'), 'busca também pelo CPF');
        $this->assertSame([$c3->id], $ids('busca=Beatriz'), 'busca também pelo nome do médico');
        $this->assertSame([], $ids('busca=ninguem'));
    }

    public function test_filtros_invalidos_voltam_422(): void
    {
        $this->entrarComoAdmin();

        $this->getJson('/api/admin/consultas?status=outra')->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->getJson('/api/admin/consultas?data_inicio=ontem')->assertStatus(422)->assertJsonValidationErrors(['data_inicio']);
        $this->getJson('/api/admin/consultas?medico_id=999')->assertStatus(422)->assertJsonValidationErrors(['medico_id']);
        $this->getJson('/api/admin/consultas?por_pagina=500')->assertStatus(422)->assertJsonValidationErrors(['por_pagina']);
    }

    public function test_busca_trata_percentual_como_texto_comum(): void
    {
        $this->paciente(['nome' => 'Ana Lima']);
        $this->entrarComoAdmin();

        $this->getJson('/api/admin/pacientes?busca=' . urlencode('%'))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_pacientes_com_busca_convenio_e_total_de_consultas(): void
    {
        $convenio = Convenio::create(['nome' => 'Unimed']);
        $ana = $this->paciente(['nome' => 'Ana Lima', 'email' => 'ana@teste.com', 'convenio_id' => $convenio->id]);
        $this->paciente(['nome' => 'Bruno Reis']);
        $this->consulta($this->medico(), $ana);
        $this->consulta($this->medico(), $ana, ['hora' => '11:00']);

        $this->entrarComoAdmin();

        $this->getJson('/api/admin/pacientes')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('data.0.nome', 'Ana Lima')
            ->assertJsonPath('data.0.convenio.nome', 'Unimed')
            ->assertJsonPath('data.0.consultas_count', 2)
            ->assertJsonMissingPath('data.0.senha_hash');

        $this->getJson('/api/admin/pacientes?busca=bruno')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/pacientes?busca=ana@teste')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_medico_com_consultas_nao_pode_ser_excluido(): void
    {
        $medico = $this->medico();
        $this->consulta($medico, $this->paciente());
        $this->entrarComoAdmin();

        $this->deleteJson("/api/admin/medicos/{$medico->id}")
            ->assertStatus(409)
            ->assertJsonPath('mensagem', fn ($texto) => str_contains($texto, 'Inative'));

        $this->assertNotNull(Medico::find($medico->id));
        $this->assertDatabaseCount('consultas', 1);

        // Listagem traz a quantidade de consultas para a tela desativar o botão
        $this->getJson('/api/admin/medicos')->assertOk()->assertJsonPath('0.consultas_count', 1);

        // Inativar continua possível
        $this->putJson("/api/admin/medicos/{$medico->id}", ['status' => 'inativo'])->assertOk();
    }

    public function test_atualizar_medico_nao_aceita_campo_em_branco(): void
    {
        $medico = $this->medico();
        $this->entrarComoAdmin();

        $this->putJson("/api/admin/medicos/{$medico->id}", ['nome' => '', 'telefone' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'telefone']);
    }

    public function test_sala_convenio_e_especialidade_em_uso_nao_podem_ser_excluidos(): void
    {
        $medico = $this->medico();
        $sala = $this->sala('Consultório 07');
        $convenio = Convenio::create(['nome' => 'Unimed']);
        $paciente = $this->paciente(['convenio_id' => $convenio->id]);
        $this->consulta($medico, $paciente, ['sala_id' => $sala->id]);

        $this->entrarComoAdmin();

        $this->deleteJson("/api/admin/salas/{$sala->id}")->assertStatus(409);
        $this->deleteJson("/api/admin/convenios/{$convenio->id}")->assertStatus(409);
        $this->deleteJson("/api/admin/especialidades/{$medico->especialidade_id}")->assertStatus(409);

        $this->assertDatabaseHas('salas', ['id' => $sala->id]);
        $this->assertDatabaseHas('convenios', ['id' => $convenio->id]);
        $this->assertDatabaseCount('consultas', 1);
        $this->assertSame($convenio->id, $paciente->fresh()->convenio_id);
    }

    public function test_sala_e_convenio_sem_uso_podem_ser_excluidos(): void
    {
        $sala = $this->sala('Sala vazia');
        $convenio = Convenio::create(['nome' => 'Sem uso']);
        $this->entrarComoAdmin();

        $this->deleteJson("/api/admin/salas/{$sala->id}")->assertOk();
        $this->deleteJson("/api/admin/convenios/{$convenio->id}")->assertOk();

        $this->assertDatabaseMissing('salas', ['id' => $sala->id]);
        $this->assertDatabaseMissing('convenios', ['id' => $convenio->id]);
        $this->deleteJson('/api/admin/salas/9999')->assertNotFound();
    }

    public function test_listagens_do_admin_trazem_a_quantidade_de_usos(): void
    {
        $medico = $this->medico();
        $sala = $this->sala();
        $convenio = Convenio::create(['nome' => 'Unimed']);
        $paciente = $this->paciente(['convenio_id' => $convenio->id]);
        $this->consulta($medico, $paciente, ['sala_id' => $sala->id]);

        $this->entrarComoAdmin();

        $this->getJson('/api/admin/especialidades')->assertOk()->assertJsonPath('0.medicos_count', 1);
        $this->getJson('/api/admin/convenios')->assertOk()->assertJsonPath('0.pacientes_count', 1);
        $this->getJson('/api/admin/salas')->assertOk()->assertJsonPath('0.consultas_count', 1);
    }

    public function test_administrador_cancela_consulta_de_qualquer_medico(): void
    {
        $consulta = $this->consulta($this->medico(), $this->paciente());
        $this->entrarComoAdmin();

        $this->putJson("/api/consultas/{$consulta->id}/cancelar")->assertOk();
        $this->assertSame('cancelada', $consulta->fresh()->status);
    }
}
