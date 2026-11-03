<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

/**
 * Esqueci/redefinir senha: o resultado chega à tela como dados de um aviso em janela
 * (<div id="aviso-servidor" data-*>), lido por resources/js/scripts/recuperacao.js.
 */
class AvisosRecuperacaoTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    private function tokenDoUltimoEmail(): array
    {
        $texto = Mail::mailer()->getSymfonyTransport()->messages()->last()->getOriginalMessage()->getTextBody();
        preg_match('#https?://\S+/redefinir-senha/\S+#', $texto, $achou);

        $link = $achou[0];
        parse_str(parse_url($link, PHP_URL_QUERY), $params);

        return [basename(parse_url($link, PHP_URL_PATH)), $params['tipo']];
    }

    public function test_pedido_de_recuperacao_abre_o_aviso_de_confira_seu_email(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $this->followingRedirects()
            ->from('/esqueci-senha')
            ->post('/esqueci-senha', ['email' => 'helena@teste.com'])
            ->assertOk()
            ->assertSee('id="aviso-servidor"', false)
            ->assertSee('data-tipo="sucesso"', false)
            ->assertSee('data-titulo="Confira o seu e-mail"', false);
    }

    public function test_pagina_sem_resultado_nao_tem_aviso(): void
    {
        $this->get('/esqueci-senha')->assertDontSee('id="aviso-servidor"', false);
        $this->get('/redefinir-senha/token?email=a@a.com&tipo=paciente')->assertDontSee('id="aviso-servidor"', false);
    }

    public function test_senha_redefinida_abre_o_aviso_que_leva_ao_login_certo(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);
        $this->post('/esqueci-senha', ['email' => 'helena@teste.com']);
        [$token, $tipo] = $this->tokenDoUltimoEmail();

        $this->followingRedirects()
            ->post('/redefinir-senha', [
                'token' => $token, 'email' => 'helena@teste.com', 'tipo' => $tipo,
                'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
            ])
            ->assertOk()
            ->assertSee('data-titulo="Senha redefinida!"', false)
            ->assertSee('data-destino="' . url('/login') . '"', false)
            ->assertDontSee('name="senha"', false); // o formulário some depois de redefinir
    }

    public function test_medico_que_redefine_a_senha_e_levado_ao_login_do_medico(): void
    {
        $this->medico(['email' => 'medico@teste.com']);
        $this->post('/esqueci-senha', ['email' => 'medico@teste.com']);
        [$token, $tipo] = $this->tokenDoUltimoEmail();

        $this->followingRedirects()
            ->post('/redefinir-senha', [
                'token' => $token, 'email' => 'medico@teste.com', 'tipo' => $tipo,
                'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
            ])
            ->assertSee('data-destino="' . url('/login-medico') . '"', false);
    }

    public function test_link_invalido_abre_o_aviso_que_leva_a_um_novo_pedido(): void
    {
        $this->paciente(['email' => 'helena@teste.com']);

        $this->followingRedirects()
            ->post('/redefinir-senha', [
                'token' => 'token-inventado', 'email' => 'helena@teste.com', 'tipo' => 'paciente',
                'senha' => 'novaSenha123', 'confirmar_senha' => 'novaSenha123',
            ])
            ->assertOk()
            ->assertSee('data-tipo="aviso"', false)
            ->assertSee('data-titulo="Link inválido ou expirado"', false)
            ->assertSee('data-destino="' . url('/esqueci-senha') . '"', false);
    }

    public function test_erro_de_validacao_da_senha_fica_no_campo_sem_aviso_de_link_invalido(): void
    {
        $this->followingRedirects()
            ->from('/redefinir-senha/token?email=a@a.com&tipo=paciente')
            ->post('/redefinir-senha', [
                'token' => 'token', 'email' => 'a@a.com', 'tipo' => 'paciente',
                'senha' => 'curta', 'confirmar_senha' => 'curta',
            ])
            ->assertSee('class="field-error"', false)
            ->assertDontSee('id="aviso-servidor"', false);
    }
}
