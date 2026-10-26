<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class RecuperacaoSenhaTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private const MENSAGEM = 'Se o e-mail estiver cadastrado, enviaremos as instruções para criar uma nova senha.';

    /** Link de redefinição do último e-mail enviado (MAIL_MAILER=array no phpunit.xml). */
    private function linkDoUltimoEmail(): ?string
    {
        $mensagens = Mail::mailer()->getSymfonyTransport()->messages();
        if ($mensagens->isEmpty()) {
            return null;
        }

        $texto = $mensagens->last()->getOriginalMessage()->getTextBody();
        preg_match('#https?://\S+/redefinir-senha/\S+#', $texto, $achou);

        return $achou[0] ?? null;
    }

    private function emailsEnviados(): int
    {
        return Mail::mailer()->getSymfonyTransport()->messages()->count();
    }

    public function test_resposta_e_igual_para_email_cadastrado_e_nao_cadastrado(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $this->from('/esqueci-senha')->post('/esqueci-senha', ['email' => 'helena@teste.com'])
            ->assertRedirect('/esqueci-senha')->assertSessionHas('status', self::MENSAGEM);
        $this->assertSame(1, $this->emailsEnviados());

        $this->from('/esqueci-senha')->post('/esqueci-senha', ['email' => 'naoexiste@teste.com'])
            ->assertRedirect('/esqueci-senha')->assertSessionHas('status', self::MENSAGEM);
        $this->assertSame(1, $this->emailsEnviados(), 'não envia e-mail para conta inexistente');
    }

    public function test_fluxo_completo_paciente_redefine_a_senha_e_o_link_so_vale_uma_vez(): void
    {
        $paciente = $this->paciente(['email' => 'helena@teste.com']);
        $tokenAntigo = $paciente->createToken('antigo')->plainTextToken;

        $this->post('/esqueci-senha', ['email' => 'helena@teste.com']);
        $link = $this->linkDoUltimoEmail();
        $this->assertNotNull($link, 'o e-mail precisa conter o link');

        // O token não é guardado em texto puro
        $this->assertStringNotContainsString(
            DB::table('password_reset_tokens')->value('token'),
            $link
        );

        $this->get($link)->assertOk();

        $params = [];
        parse_str(parse_url($link, PHP_URL_QUERY), $params);
        $token = basename(parse_url($link, PHP_URL_PATH));

        $this->post('/redefinir-senha', [
            'token' => $token, 'email' => 'helena@teste.com', 'tipo' => $params['tipo'],
            'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
        ])->assertSessionHas('status');

        $this->assertTrue(Hash::check('novaSenha123', $paciente->fresh()->senha_hash));
        $this->assertSame(0, $paciente->tokens()->count(), 'as sessões abertas são encerradas');
        $this->assertDatabaseCount('password_reset_tokens', 0);

        // Login com a senha nova funciona; com a antiga não
        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'novaSenha123'])->assertOk();
        $this->postJson('/api/login/paciente', ['email' => 'helena@teste.com', 'senha' => 'paciente123'])->assertStatus(422);

        // O mesmo link não funciona de novo
        $this->post('/redefinir-senha', [
            'token' => $token, 'email' => 'helena@teste.com', 'tipo' => $params['tipo'],
            'senha' => 'outraSenha123', 'confirmar_senha' => 'outraSenha123',
        ])->assertSessionHasErrors('senha');
        $this->assertTrue(Hash::check('novaSenha123', $paciente->fresh()->senha_hash));
    }

    public function test_medico_ativo_tambem_recupera_a_senha_mas_inativo_nao_recebe_email(): void
    {
        $this->medico(['email' => 'ativo@teste.com']);
        $this->medico(['email' => 'inativo@teste.com', 'status' => 'inativo']);

        $this->post('/esqueci-senha', ['email' => 'ativo@teste.com']);
        $this->assertSame(1, $this->emailsEnviados());
        $this->assertStringContainsString('tipo=medico', $this->linkDoUltimoEmail());

        $this->post('/esqueci-senha', ['email' => 'inativo@teste.com']);
        $this->assertSame(1, $this->emailsEnviados());
    }

    public function test_token_errado_ou_expirado_e_recusado(): void
    {
        $paciente = $this->paciente(['email' => 'helena@teste.com']);

        $this->post('/esqueci-senha', ['email' => 'helena@teste.com']);
        $token = basename(parse_url($this->linkDoUltimoEmail(), PHP_URL_PATH));

        $dados = fn (string $t) => [
            'token' => $t, 'email' => 'helena@teste.com', 'tipo' => 'paciente',
            'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
        ];

        $this->post('/redefinir-senha', $dados('token-inventado'))->assertSessionHasErrors('senha');

        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/redefinir-senha', $dados($token))->assertSessionHasErrors('senha');

        $this->assertTrue(Hash::check('paciente123', $paciente->fresh()->senha_hash), 'a senha não pode mudar');
    }

    public function test_confirmacao_diferente_e_senha_curta_sao_recusadas_em_portugues(): void
    {
        $this->post('/redefinir-senha', [
            'token' => 'x', 'email' => 'helena@teste.com', 'tipo' => 'paciente',
            'senha' => 'curta', 'confirmar_senha' => 'diferente',
        ])->assertSessionHasErrors(['senha', 'confirmar_senha']);
    }

    public function test_email_invalido_no_pedido(): void
    {
        $this->post('/esqueci-senha', ['email' => 'isso-nao-e-email'])->assertSessionHasErrors('email');
    }
}
