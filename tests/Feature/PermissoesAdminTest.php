<?php

namespace Tests\Feature;

use App\Models\Medico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Antes, qualquer usuário autenticado (até um paciente) conseguia usar /api/admin/*.
 */
class PermissoesAdminTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    public static function rotasAdmin(): array
    {
        return [
            'listar médicos'       => ['GET', '/api/admin/medicos'],
            'ver médico'           => ['GET', '/api/admin/medicos/1'],
            'excluir médico'       => ['DELETE', '/api/admin/medicos/1'],
            'criar especialidade'  => ['POST', '/api/admin/especialidades'],
            'excluir especialidade'=> ['DELETE', '/api/admin/especialidades/1'],
            'listar salas'         => ['GET', '/api/admin/salas'],
            'criar sala'           => ['POST', '/api/admin/salas'],
            'listar convênios'     => ['GET', '/api/admin/convenios'],
            'criar convênio'       => ['POST', '/api/admin/convenios'],
        ];
    }

    #[DataProvider('rotasAdmin')]
    public function test_visitante_recebe_401(string $metodo, string $url): void
    {
        $this->json($metodo, $url)->assertStatus(401);
    }

    #[DataProvider('rotasAdmin')]
    public function test_paciente_recebe_403(string $metodo, string $url): void
    {
        $this->medico(); // id 1 existe, para provar que o bloqueio é por perfil e não por 404
        Sanctum::actingAs($this->paciente());

        $this->json($metodo, $url)->assertStatus(403);
    }

    #[DataProvider('rotasAdmin')]
    public function test_medico_recebe_403(string $metodo, string $url): void
    {
        Sanctum::actingAs($this->medico());

        $this->json($metodo, $url)->assertStatus(403);
        $this->assertDatabaseCount('medicos', 1);
    }

    public function test_administrador_gerencia_medicos_especialidades_salas_e_convenios(): void
    {
        Sanctum::actingAs($this->administrador());
        $especialidade = $this->especialidadeId('Pediatria');

        $id = $this->postJson('/api/admin/medicos', [
            'nome' => 'Dra. Nova', 'crm' => 'RS-99999', 'especialidade_id' => $especialidade,
            'telefone' => '(54) 99999-0000', 'email' => 'nova@teste.com', 'senha' => 'medico123',
        ])->assertCreated()->json('medico.id');

        $this->assertNotNull($id);
        $this->getJson('/api/admin/medicos')->assertOk()->assertJsonFragment(['email' => 'nova@teste.com']);
        $this->putJson("/api/admin/medicos/{$id}", ['status' => 'inativo'])->assertOk();
        $this->assertSame('inativo', Medico::find($id)->status);

        $this->postJson('/api/admin/especialidades', ['nome' => 'Urologia'])->assertCreated();
        $this->postJson('/api/admin/salas', ['identificacao' => 'Sala 9'])->assertCreated();
        $this->postJson('/api/admin/convenios', ['nome' => 'Convênio X'])->assertCreated();
        $this->getJson('/api/admin/salas')->assertOk()->assertJsonFragment(['identificacao' => 'Sala 9']);
        $this->getJson('/api/admin/convenios')->assertOk()->assertJsonFragment(['nome' => 'Convênio X']);

        $this->deleteJson("/api/admin/medicos/{$id}")->assertOk();
        $this->assertNull(Medico::find($id));
    }

    public function test_dados_publicos_nao_exigem_login_e_so_listam_medicos_ativos(): void
    {
        $this->medico(['nome' => 'Dr. Ativo']);
        $this->medico(['nome' => 'Dr. Inativo', 'status' => 'inativo']);

        $this->getJson('/api/especialidades')->assertOk();
        $this->getJson('/api/medicos')->assertOk()
            ->assertJsonFragment(['nome' => 'Dr. Ativo'])
            ->assertJsonMissing(['nome' => 'Dr. Inativo'])
            ->assertJsonMissingPath('0.senha_hash')
            ->assertJsonMissingPath('0.email');
    }

    public function test_medico_desativado_depois_do_login_perde_o_acesso_as_rotas_de_medico(): void
    {
        $medico = $this->medico();
        $consulta = $this->consulta($medico, $this->paciente());
        $medico->update(['status' => 'inativo']);
        Sanctum::actingAs($medico);

        $this->postJson('/api/atendimentos', ['consulta_id' => $consulta->id, 'diagnostico' => 'x'])->assertStatus(403);
    }
}
