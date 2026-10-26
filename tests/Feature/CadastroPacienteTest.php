<?php

namespace Tests\Feature;

use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class CadastroPacienteTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private function dados(array $sobrescrever = []): array
    {
        return array_merge([
            'nome'            => 'Beatriz Teste Silva',
            'cpf'             => '321.654.987-00',
            'data_nascimento' => '1992-03-14',
            'sexo'            => 'F',
            'email'           => 'beatriz@teste.com',
            'telefone'        => '(51) 99999-0000',
            'senha'           => 'segredo123',
            'confirmar_senha' => 'segredo123',
            'termos'          => true,
        ], $sobrescrever);
    }

    public function test_cadastro_cria_paciente_e_ja_devolve_token_e_perfil(): void
    {
        $this->postJson('/api/cadastrar', $this->dados())
            ->assertCreated()
            ->assertJsonPath('perfil', 'paciente')
            ->assertJsonPath('usuario.email', 'beatriz@teste.com')
            ->assertJsonStructure(['access_token', 'token_type'])
            ->assertJsonMissingPath('usuario.senha_hash');

        $paciente = Paciente::where('email', 'beatriz@teste.com')->firstOrFail();
        $this->assertNotSame('segredo123', $paciente->senha_hash, 'a senha precisa ser salva criptografada');
    }

    public function test_depois_de_cadastrar_o_login_funciona(): void
    {
        $this->postJson('/api/cadastrar', $this->dados())->assertCreated();

        $this->postJson('/api/login/paciente', ['email' => 'beatriz@teste.com', 'senha' => 'segredo123'])
            ->assertOk();
    }

    public function test_aceita_senha_confirmation_no_padrao_do_laravel(): void
    {
        $dados = $this->dados();
        unset($dados['confirmar_senha']);
        $dados['senha_confirmation'] = 'segredo123';

        $this->postJson('/api/cadastrar', $dados)->assertCreated();
    }

    public function test_confirmacao_diferente_da_senha_e_recusada(): void
    {
        $this->postJson('/api/cadastrar', $this->dados(['confirmar_senha' => 'outra-coisa']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['confirmar_senha'])
            ->assertJsonPath('errors.confirmar_senha.0', 'A confirmação da senha não confere com a senha.');
    }

    public function test_cpf_e_email_repetidos_sao_recusados_com_mensagem_em_portugues(): void
    {
        $this->paciente(['cpf' => '321.654.987-00', 'email' => 'beatriz@teste.com']);

        $this->postJson('/api/cadastrar', $this->dados())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cpf', 'email'])
            ->assertJsonPath('errors.cpf.0', 'Este CPF já está cadastrado.')
            ->assertJsonPath('errors.email.0', 'Este e-mail já está cadastrado.');
    }

    public function test_senha_curta_e_data_de_nascimento_no_futuro_sao_recusadas(): void
    {
        $this->postJson('/api/cadastrar', $this->dados([
            'senha' => '123', 'confirmar_senha' => '123',
            'data_nascimento' => now()->addDay()->toDateString(),
        ]))->assertStatus(422)->assertJsonValidationErrors(['senha', 'data_nascimento']);
    }

    public function test_termos_de_uso_recusados_impedem_o_cadastro(): void
    {
        $this->postJson('/api/cadastrar', $this->dados(['termos' => false]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['termos']);

        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_campos_obrigatorios(): void
    {
        $this->postJson('/api/cadastrar', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'cpf', 'data_nascimento', 'sexo', 'email', 'telefone', 'senha', 'confirmar_senha']);
    }
}
