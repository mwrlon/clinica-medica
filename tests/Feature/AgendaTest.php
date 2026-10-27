<?php

namespace Tests\Feature;

use App\Models\Consulta;
use App\Models\Sala;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Horários livres, sala automática, regras de expediente e reagendamento.
 *
 * O relógio é fixado na segunda-feira 02/11/2026 às 08:00 (America/Sao_Paulo):
 * quarta = 04/11, sábado = 07/11 e domingo = 08/11.
 */
class AgendaTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private const QUARTA  = '2026-11-04';
    private const SABADO  = '2026-11-07';
    private const DOMINGO = '2026-11-08';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-02 08:00:00');
    }

    private function horarios($medico, string $data, string $extra = ''): \Illuminate\Testing\TestResponse
    {
        return $this->getJson("/api/medicos/{$medico->id}/horarios?data={$data}{$extra}");
    }

    /* ---------------------------- horários livres ---------------------------- */

    public function test_dia_util_tem_horarios_de_7h_as_18h30_de_meia_em_meia_hora(): void
    {
        $medico = $this->medico();
        Sanctum::actingAs($this->paciente());
        $this->sala();

        $horarios = $this->horarios($medico, self::QUARTA)->assertOk()->assertJsonPath('fechado', false)->json('horarios');

        $this->assertCount(24, $horarios);
        $this->assertSame('07:00', $horarios[0]);
        $this->assertSame('07:30', $horarios[1]);
        $this->assertSame('18:30', end($horarios));
    }

    public function test_sabado_vai_das_8h_as_11h30_e_domingo_a_clinica_fecha(): void
    {
        $medico = $this->medico();
        Sanctum::actingAs($this->paciente());
        $this->sala();

        $sabado = $this->horarios($medico, self::SABADO)->assertOk()->json('horarios');
        $this->assertSame(['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'], $sabado);

        $this->horarios($medico, self::DOMINGO)->assertOk()
            ->assertJsonPath('fechado', true)->assertJsonPath('horarios', []);
    }

    public function test_hoje_nao_oferece_horarios_que_ja_passaram(): void
    {
        $this->travelTo('2026-11-04 10:10:00');
        $medico = $this->medico();
        Sanctum::actingAs($this->paciente());
        $this->sala();

        $horarios = $this->horarios($medico, self::QUARTA)->assertOk()->json('horarios');

        $this->assertSame('10:30', $horarios[0]);
    }

    public function test_exclui_horarios_do_medico_e_do_proprio_paciente(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $this->sala();

        // O médico está ocupado às 09:00 (com outro paciente) e o paciente às 10:00 (com outro médico)
        $this->consulta($medico, $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00']);
        $this->consulta($this->medico(), $paciente, ['data' => self::QUARTA, 'hora' => '10:00', 'sala_id' => $this->sala('Consultório 02')->id]);

        Sanctum::actingAs($paciente);
        $horarios = $this->horarios($medico, self::QUARTA)->json('horarios');

        $this->assertNotContains('09:00', $horarios);
        $this->assertNotContains('10:00', $horarios);
        $this->assertContains('09:30', $horarios);
        $this->assertCount(22, $horarios);
    }

    public function test_consulta_cancelada_nao_ocupa_horario(): void
    {
        $medico = $this->medico();
        $this->consulta($medico, $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00', 'status' => 'cancelada']);
        Sanctum::actingAs($this->paciente());

        $this->assertContains('09:00', $this->horarios($medico, self::QUARTA)->json('horarios'));
    }

    public function test_horario_some_quando_todas_as_salas_estao_ocupadas(): void
    {
        $medico = $this->medico();
        $s1 = $this->sala('Consultório 01');
        $s2 = $this->sala('Consultório 02');

        $this->consulta($this->medico(), $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00', 'sala_id' => $s1->id]);
        $this->consulta($this->medico(), $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00', 'sala_id' => $s2->id]);
        $this->consulta($this->medico(), $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:30', 'sala_id' => $s1->id]);

        Sanctum::actingAs($this->paciente());
        $horarios = $this->horarios($medico, self::QUARTA)->json('horarios');

        $this->assertNotContains('09:00', $horarios, 'as duas salas estão ocupadas');
        $this->assertContains('09:30', $horarios, 'ainda sobra uma sala');
    }

    public function test_horarios_exigem_login_data_futura_e_medico_ativo(): void
    {
        $medico = $this->medico();

        $this->getJson("/api/medicos/{$medico->id}/horarios?data=" . self::QUARTA)->assertStatus(401);

        Sanctum::actingAs($this->paciente());
        $this->getJson("/api/medicos/{$medico->id}/horarios")->assertStatus(422)->assertJsonValidationErrors(['data']);
        $this->horarios($medico, '2026-11-01')->assertStatus(422)->assertJsonValidationErrors(['data']);

        $inativo = $this->medico(['status' => 'inativo']);
        $this->horarios($inativo, self::QUARTA)->assertNotFound();
        $this->getJson('/api/medicos/9999/horarios?data=' . self::QUARTA)->assertNotFound();
    }

    public function test_reagendando_o_horario_da_propria_consulta_conta_como_livre(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consulta($medico, $paciente, ['data' => self::QUARTA, 'hora' => '09:00']);
        Sanctum::actingAs($paciente);

        $this->assertNotContains('09:00', $this->horarios($medico, self::QUARTA)->json('horarios'));
        $this->assertContains('09:00', $this->horarios($medico, self::QUARTA, "&ignorar={$consulta->id}")->json('horarios'));
    }

    public function test_ignorar_consulta_de_outra_pessoa_nao_libera_o_horario(): void
    {
        $medico = $this->medico();
        $alheia = $this->consulta($medico, $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00']);
        Sanctum::actingAs($this->paciente());

        $this->assertNotContains('09:00', $this->horarios($medico, self::QUARTA, "&ignorar={$alheia->id}")->json('horarios'));
    }

    /* ----------------------- agendar: expediente e sala ----------------------- */

    public function test_paciente_so_marca_dentro_do_expediente(): void
    {
        $medico = $this->medico();
        Sanctum::actingAs($this->paciente());
        $sala = $this->sala();

        foreach ([['06:30', self::QUARTA], ['09:15', self::QUARTA], ['19:00', self::QUARTA], ['12:00', self::SABADO], ['09:00', self::DOMINGO]] as [$hora, $data]) {
            $this->postJson('/api/consultas', ['medico_id' => $medico->id, 'sala_id' => $sala->id, 'data' => $data, 'hora' => $hora])
                ->assertStatus(422)
                ->assertJsonPath('mensagem', 'Horário fora do atendimento da clínica. Escolha um dos horários disponíveis.');
        }

        $this->postJson('/api/consultas', ['medico_id' => $medico->id, 'data' => self::SABADO, 'hora' => '11:30'])->assertCreated();
    }

    public function test_nao_marca_horario_que_ja_passou_hoje(): void
    {
        $this->travelTo('2026-11-04 10:10:00');
        $medico = $this->medico();
        Sanctum::actingAs($this->paciente());
        $this->sala();

        $this->postJson('/api/consultas', ['medico_id' => $medico->id, 'data' => self::QUARTA, 'hora' => '09:30'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'Este horário já passou. Escolha uma data e hora futuras.');
        $this->postJson('/api/consultas', ['medico_id' => $medico->id, 'data' => self::QUARTA, 'hora' => '10:30'])->assertCreated();
    }

    public function test_medico_pode_fazer_encaixe_fora_da_grade(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $this->sala();
        Sanctum::actingAs($medico);

        $this->postJson('/api/consultas', ['paciente_id' => $paciente->id, 'data' => self::DOMINGO, 'hora' => '20:15', 'observacoes' => 'Encaixe'])
            ->assertCreated()
            ->assertJsonPath('consulta.medico_id', $medico->id)
            ->assertJsonPath('consulta.observacoes', 'Encaixe');
    }

    public function test_sem_sala_a_primeira_livre_e_escolhida_automaticamente(): void
    {
        $s1 = $this->sala('Consultório 01');
        $s2 = $this->sala('Consultório 02');
        $dia = ['data' => self::QUARTA, 'hora' => '09:00'];

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id] + $dia)
            ->assertCreated()->assertJsonPath('consulta.sala_id', $s1->id)->assertJsonPath('consulta.sala.identificacao', 'Consultório 01');

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id] + $dia)
            ->assertCreated()->assertJsonPath('consulta.sala_id', $s2->id);
    }

    public function test_sem_sala_livre_o_agendamento_e_recusado(): void
    {
        $this->sala('Consultório 01');
        $dia = ['data' => self::QUARTA, 'hora' => '09:00'];

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id] + $dia)->assertCreated();

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id] + $dia)
            ->assertStatus(422)
            ->assertJsonPath('mensagem', 'Não há sala disponível neste horário. Escolha outro horário.');
    }

    public function test_sala_escolhida_ocupada_e_recusada(): void
    {
        $s1 = $this->sala('Consultório 01');
        $this->sala('Consultório 02');
        $this->consulta($this->medico(), $this->paciente(), ['data' => self::QUARTA, 'hora' => '09:00', 'sala_id' => $s1->id]);

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id, 'sala_id' => $s1->id, 'data' => self::QUARTA, 'hora' => '09:00'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'A sala escolhida já está ocupada neste horário.');
    }

    /* -------------------------------- reagendar ------------------------------- */

    private function consultaMarcada($medico, $paciente, string $hora = '09:00', array $extra = []): Consulta
    {
        return $this->consulta($medico, $paciente, array_merge(['data' => self::QUARTA, 'hora' => $hora], $extra));
    }

    public function test_paciente_reagenda_e_o_horario_antigo_fica_livre(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($medico, $paciente);
        $salaOriginal = $consulta->sala_id;
        Sanctum::actingAs($paciente);

        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::SABADO, 'hora' => '10:30'])
            ->assertOk()
            ->assertJsonPath('mensagem', 'Consulta reagendada com sucesso!')
            ->assertJsonPath('consulta.data', self::SABADO)
            ->assertJsonPath('consulta.status', 'agendada')
            ->assertJsonPath('consulta.sala_id', $salaOriginal);

        $this->assertSame(self::SABADO, $consulta->fresh()->data->format('Y-m-d'));
        $this->assertStringStartsWith('10:30', $consulta->fresh()->hora);

        // O horário antigo voltou a ficar disponível para outro paciente
        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $medico->id, 'data' => self::QUARTA, 'hora' => '09:00'])->assertCreated();
    }

    public function test_reagendar_para_o_mesmo_horario_e_recusado(): void
    {
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($this->medico(), $paciente);
        Sanctum::actingAs($paciente);

        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::QUARTA, 'hora' => '09:00'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'Escolha um dia ou horário diferente do atual.');
    }

    public function test_reagendar_nao_pode_bater_com_outra_consulta_do_medico_ou_do_paciente(): void
    {
        $medico = $this->medico();
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($medico, $paciente, '09:00');
        $this->consultaMarcada($medico, $this->paciente(), '10:00');                 // médico ocupado às 10:00
        $this->consultaMarcada($this->medico(), $paciente, '11:00', ['sala_id' => $this->sala('Consultório 02')->id]); // paciente ocupado às 11:00
        Sanctum::actingAs($paciente);

        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::QUARTA, 'hora' => '10:00'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'O médico já possui uma consulta agendada para este dia e horário.');
        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::QUARTA, 'hora' => '11:00'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'O paciente já possui uma consulta agendada para este mesmo horário.');
    }

    public function test_reagendar_muda_de_sala_quando_a_atual_esta_ocupada_no_novo_horario(): void
    {
        $s1 = $this->sala('Consultório 01');
        $s2 = $this->sala('Consultório 02');
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($this->medico(), $paciente, '09:00', ['sala_id' => $s1->id]);
        $this->consultaMarcada($this->medico(), $this->paciente(), '10:00', ['sala_id' => $s1->id]);
        Sanctum::actingAs($paciente);

        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::QUARTA, 'hora' => '10:00'])
            ->assertOk()->assertJsonPath('consulta.sala_id', $s2->id);
    }

    public function test_reagendar_sem_nenhuma_sala_livre_e_recusado(): void
    {
        $s1 = $this->sala('Consultório 01');
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($this->medico(), $paciente, '09:00', ['sala_id' => $s1->id]);
        $this->consultaMarcada($this->medico(), $this->paciente(), '10:00', ['sala_id' => $s1->id]);
        Sanctum::actingAs($paciente);

        $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::QUARTA, 'hora' => '10:00'])
            ->assertStatus(422)->assertJsonPath('mensagem', 'Não há sala disponível neste horário. Escolha outro horário.');
    }

    public function test_so_consulta_agendada_pode_ser_reagendada(): void
    {
        $paciente = $this->paciente();
        $realizada = $this->consultaMarcada($this->medico(), $paciente, '09:00', ['status' => 'realizada']);
        $cancelada = $this->consultaMarcada($this->medico(), $paciente, '10:00', ['status' => 'cancelada']);
        Sanctum::actingAs($paciente);

        foreach ([$realizada, $cancelada] as $consulta) {
            $this->putJson("/api/consultas/{$consulta->id}/reagendar", ['data' => self::SABADO, 'hora' => '09:00'])
                ->assertStatus(422)->assertJsonPath('mensagem', 'Só é possível reagendar consultas que ainda estão agendadas.');
        }
    }

    public function test_reagendar_valida_data_hora_expediente_e_horario_passado(): void
    {
        $paciente = $this->paciente();
        $consulta = $this->consultaMarcada($this->medico(), $paciente);
        Sanctum::actingAs($paciente);
        $url = "/api/consultas/{$consulta->id}/reagendar";

        $this->putJson($url, [])->assertStatus(422)->assertJsonValidationErrors(['data', 'hora']);
        $this->putJson($url, ['data' => '2026-11-01', 'hora' => '09:00'])->assertStatus(422)->assertJsonValidationErrors(['data']);
        $this->putJson($url, ['data' => self::DOMINGO, 'hora' => '09:00'])->assertStatus(422)
            ->assertJsonPath('mensagem', 'Horário fora do atendimento da clínica. Escolha um dos horários disponíveis.');
        $this->putJson($url, ['data' => self::QUARTA, 'hora' => '09:10'])->assertStatus(422);

        $this->travelTo('2026-11-03 12:00:00');
        $this->putJson($url, ['data' => '2026-11-03', 'hora' => '08:00'])->assertStatus(422)
            ->assertJsonPath('mensagem', 'Este horário já passou. Escolha uma data e hora futuras.');
    }

    public function test_so_quem_participa_da_consulta_pode_reagendar(): void
    {
        $medico = $this->medico();
        $dono = $this->paciente();
        $consulta = $this->consultaMarcada($medico, $dono);
        $url = "/api/consultas/{$consulta->id}/reagendar";
        $novo = ['data' => self::SABADO, 'hora' => '09:00'];

        $this->putJson($url, $novo)->assertStatus(401);

        Sanctum::actingAs($this->paciente());
        $this->putJson($url, $novo)->assertStatus(403);

        Sanctum::actingAs($this->medico());
        $this->putJson($url, $novo)->assertStatus(403);

        Sanctum::actingAs($medico);
        $this->putJson($url, $novo)->assertOk();

        $this->putJson('/api/consultas/9999/reagendar', $novo)->assertNotFound();
    }

    public function test_cada_sala_aparece_ocupada_so_pelas_consultas_ativas(): void
    {
        // Regressão: a consulta cancelada não pode bloquear a sala nem a escolha automática
        $s1 = $this->sala('Consultório 01');
        $this->consultaMarcada($this->medico(), $this->paciente(), '09:00', ['sala_id' => $s1->id, 'status' => 'cancelada']);

        Sanctum::actingAs($this->paciente());
        $this->postJson('/api/consultas', ['medico_id' => $this->medico()->id, 'data' => self::QUARTA, 'hora' => '09:00'])
            ->assertCreated()->assertJsonPath('consulta.sala_id', $s1->id);

        $this->assertSame(1, Sala::count());
    }
}
