<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class AutenticacaoTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    public function test_paciente_faz_login_e_recebe_token_perfil_e_usuario(): void
    {
        $paciente = $this->paciente(['email' => 'helena@teste.com']);

        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'perfil', 'usuario' => ['id', 'nome', 'email']])
            ->assertJsonPath('perfil', 'paciente')
            ->assertJsonPath('usuario.id', $paciente->id)
            ->assertJsonMissingPath('usuario.senha_hash');
    }

    public function test_medico_faz_login(): void
    {
        $this->medico(['email' => 'rafael@teste.com']);

        $this->postJson('/api/login/medico', ['email' => 'rafael@teste.com', 'senha' => 'medico123'])
            ->assertOk()
            ->assertJsonPath('perfil', 'medico')
            ->assertJsonStructure(['access_token']);
    }

    public function test_administrador_faz_login(): void
    {
        $this->administrador(['email' => 'admin@teste.com']);

        $this->postJson('/api/login/admin', ['email' => 'admin@teste.com', 'senha' => 'admin12345'])
            ->assertOk()
            ->assertJsonPath('perfil', 'admin')
            ->assertJsonStructure(['access_token']);
    }

    public function test_senha_errada_ou_email_inexistente_responde_422_no_campo_email(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'errada'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Credenciais inválidas. Verifique e-mail e senha.');

        $this->postJson('/api/login/paciente', ['email' => 'ninguem@teste.com', 'senha' => 'paciente123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_nao_aceita_credencial_de_outro_perfil(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $this->postJson('/api/login/medico', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])
            ->assertStatus(422);
    }

    public function test_login_exige_email_e_senha_com_mensagens_em_portugues(): void
    {
        $this->postJson('/api/login/paciente', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'senha']);
    }

    public function test_medico_inativo_nao_entra(): void
    {
        $this->medico(['email' => 'inativo@teste.com', 'status' => 'inativo']);

        $this->postJson('/api/login/medico', ['email' => 'inativo@teste.com', 'senha' => 'medico123'])
            ->assertStatus(403)
            ->assertJsonStructure(['mensagem']);
    }

    public function test_perfil_devolve_o_usuario_do_token(): void
    {
        $this->paciente(['email' => 'helena@teste.com', 'nome' => 'Helena Marques']);

        $token = $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])
            ->json('access_token');

        $this->withToken($token)->getJson('/api/perfil')
            ->assertOk()
            ->assertJsonPath('usuario.nome', 'Helena Marques')
            ->assertJsonPath('perfil', 'paciente');
    }

    public function test_rotas_protegidas_sem_token_respondem_401_em_json(): void
    {
        $this->getJson('/api/perfil')->assertStatus(401);
        // Mesmo sem o cabeçalho "Accept: application/json" a API não redireciona para /login
        $this->get('/api/consultas')->assertStatus(401)->assertJsonStructure(['message']);
    }

    public function test_logout_revoga_o_token(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $token = $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])
            ->json('access_token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Nova requisição com o mesmo token: o guard guarda o usuário da requisição anterior
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/perfil')->assertStatus(401);
    }
}
