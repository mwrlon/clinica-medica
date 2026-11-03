<?php

namespace Tests\Feature;

use App\Models\Convenio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Aba "Meu Perfil": cada usuário edita os próprios dados e troca a própria senha.
 */
class PerfilTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private function dadosDoPaciente(array $troca = []): array
    {
        return array_merge([
            'nome'            => 'Helena Souza Marques',
            'data_nascimento' => '1991-02-03',
            'sexo'            => 'F',
            'telefone'        => '(54) 97777-0000',
            'email'           => 'helena.nova@teste.com',
            'endereco'        => 'Rua A, 10',
            'convenio_id'     => null,
        ], $troca);
    }

    public function test_visitante_recebe_401(): void
    {
        $this->putJson('/api/perfil', [])->assertStatus(401);
        $this->putJson('/api/perfil/senha', [])->assertStatus(401);
    }

    public function test_paciente_atualiza_os_proprios_dados_e_escolhe_convenio(): void
    {
        $paciente = $this->paciente(['cpf' => '111.111.111-11']);
        $convenio = Convenio::create(['nome' => 'Unimed']);
        Sanctum::actingAs($paciente);

        $this->putJson('/api/perfil', $this->dadosDoPaciente(['convenio_id' => $convenio->id]))
            ->assertOk()
            ->assertJsonPath('usuario.nome', 'Helena Souza Marques')
            ->assertJsonPath('usuario.convenio.nome', 'Unimed')
            ->assertJsonMissingPath('usuario.senha_hash');

        $paciente->refresh();
        $this->assertSame('helena.nova@teste.com', $paciente->email);
        $this->assertSame($convenio->id, $paciente->convenio_id);
        $this->assertSame('Rua A, 10', $paciente->endereco);

        // Voltar para "particular" (sem convênio)
        $this->putJson('/api/perfil', $this->dadosDoPaciente(['convenio_id' => null]))->assertOk();
        $this->assertNull($paciente->fresh()->convenio_id);
    }

    public function test_paciente_nao_altera_o_cpf_nem_o_perfil_de_outra_pessoa(): void
    {
        $paciente = $this->paciente(['cpf' => '111.111.111-11']);
        $outro = $this->paciente(['cpf' => '222.222.222-22', 'nome' => 'Outro Paciente']);
        Sanctum::actingAs($paciente);

        $this->putJson('/api/perfil', $this->dadosDoPaciente(['cpf' => '999.999.999-99', 'id' => $outro->id]))->assertOk();

        $this->assertSame('111.111.111-11', $paciente->fresh()->cpf);
        $this->assertSame('Outro Paciente', $outro->fresh()->nome);
    }

    public function test_dados_invalidos_do_paciente_voltam_em_portugues(): void
    {
        $paciente = $this->paciente();
        $outro = $this->paciente(['email' => 'ocupado@teste.com']);
        Sanctum::actingAs($paciente);

        $this->putJson('/api/perfil', $this->dadosDoPaciente(['email' => 'ocupado@teste.com', 'nome' => '', 'convenio_id' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'nome', 'convenio_id'])
            ->assertJsonPath('errors.email.0', 'Este e-mail já está cadastrado.');

        // O próprio e-mail não conta como duplicado
        $this->putJson('/api/perfil', $this->dadosDoPaciente(['email' => $paciente->email]))->assertOk();
        $this->assertNotNull($outro->fresh());
    }

    public function test_medico_atualiza_nome_telefone_e_email_mas_nao_crm_nem_especialidade(): void
    {
        $medico = $this->medico(['crm' => 'RS-11111']);
        $outraEspecialidade = $this->especialidadeId('Pediatria');
        Sanctum::actingAs($medico);

        $this->putJson('/api/perfil', [
            'nome' => 'Dra. Nova Silva', 'telefone' => '(54) 90000-1111', 'email' => 'nova@teste.com',
            'crm' => 'RS-99999', 'especialidade_id' => $outraEspecialidade, 'status' => 'inativo',
        ])->assertOk()->assertJsonPath('usuario.especialidade.nome', 'Cardiologia');

        $medico->refresh();
        $this->assertSame('Dra. Nova Silva', $medico->nome);
        $this->assertSame('RS-11111', $medico->crm);
        $this->assertSame('ativo', $medico->status);
        $this->assertNotSame($outraEspecialidade, $medico->especialidade_id);
    }

    public function test_administrador_atualiza_nome_e_email(): void
    {
        $admin = $this->administrador();
        Sanctum::actingAs($admin);

        $this->putJson('/api/perfil', ['nome' => 'Admin Novo', 'email' => 'admin.novo@teste.com'])->assertOk();

        $this->assertSame('Admin Novo', $admin->fresh()->nome);
        $this->assertSame('admin.novo@teste.com', $admin->fresh()->email);
    }

    public function test_troca_de_senha_exige_a_senha_atual(): void
    {
        $paciente = $this->paciente();
        Sanctum::actingAs($paciente);

        $this->putJson('/api/perfil/senha', [
            'senha_atual' => 'errada', 'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
        ])->assertStatus(422)
            ->assertJsonPath('errors.senha_atual.0', 'A senha atual está incorreta.');

        $this->assertTrue(Hash::check('paciente123', $paciente->fresh()->senha_hash));
    }

    public function test_troca_de_senha_valida_tamanho_confirmacao_e_igualdade(): void
    {
        Sanctum::actingAs($this->paciente());

        $this->putJson('/api/perfil/senha', ['senha_atual' => 'paciente123', 'senha' => 'curta', 'confirmar_senha' => 'curta'])
            ->assertStatus(422)->assertJsonValidationErrors(['senha']);

        $this->putJson('/api/perfil/senha', ['senha_atual' => 'paciente123', 'senha' => 'novaSenha123', 'confirmar_senha' => 'outraCoisa1'])
            ->assertStatus(422)->assertJsonValidationErrors(['confirmar_senha']);

        $this->putJson('/api/perfil/senha', ['senha_atual' => 'paciente123', 'senha' => 'paciente123', 'confirmar_senha' => 'paciente123'])
            ->assertStatus(422)
            ->assertJsonPath('errors.senha.0', 'A nova senha deve ser diferente da senha atual.');
    }

    public function test_troca_de_senha_funciona_e_encerra_as_outras_sessoes(): void
    {
        $paciente = $this->paciente(['email' => 'helena@teste.com']);
        $outraSessao = $paciente->createToken('celular');

        // Sessão "atual" de verdade (token Bearer), para provar que ela continua valendo
        $atual = $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])
            ->json('access_token');

        $this->withToken($atual)
            ->putJson('/api/perfil/senha', ['senha_atual' => 'paciente123', 'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123'])
            ->assertOk();

        $this->assertTrue(Hash::check('novaSenha123', $paciente->fresh()->senha_hash));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $outraSessao->accessToken->id]);
        $this->assertSame(1, $paciente->tokens()->count(), 'só a sessão atual continua');

        // Login com a senha nova funciona; com a antiga não
        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'novaSenha123'])->assertOk();
        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])->assertStatus(422);
    }

    public function test_medico_e_administrador_tambem_trocam_a_senha(): void
    {
        Sanctum::actingAs($this->medico());
        $this->putJson('/api/perfil/senha', ['senha_atual' => 'medico123', 'senha' => 'medicoNovo123', 'confirmar_senha' => 'medicoNovo123'])->assertOk();

        Sanctum::actingAs($this->administrador());
        $this->putJson('/api/perfil/senha', ['senha_atual' => 'admin12345', 'senha' => 'adminNovo123', 'confirmar_senha' => 'adminNovo123'])->assertOk();
    }

    public function test_lista_publica_de_convenios_para_o_formulario(): void
    {
        Convenio::create(['nome' => 'Unimed']);
        Convenio::create(['nome' => 'Amil']);

        $this->getJson('/api/convenios')
            ->assertOk()
            ->assertJsonPath('0.nome', 'Amil')   // em ordem alfabética
            ->assertJsonPath('1.nome', 'Unimed')
            ->assertJsonCount(2);
    }
}
