<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaginasTest extends TestCase
{
    use RefreshDatabase;

    public static function paginas(): array
    {
        return [
            'início'            => ['/'],
            'login do paciente' => ['/login'],
            'login (alias)'     => ['/login-paciente'],
            'login do médico'   => ['/login-medico'],
            'cadastro'          => ['/cadastro'],
            'esqueci a senha'   => ['/esqueci-senha'],
            'redefinir senha'   => ['/redefinir-senha/um-token?email=a@a.com&tipo=paciente'],
            'painel do médico'  => ['/medico'],
            'painel do paciente'=> ['/paciente'],
        ];
    }

    #[DataProvider('paginas')]
    public function test_pagina_abre(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_formulario_de_cadastro_tem_o_campo_de_confirmacao_ligado_ao_rotulo(): void
    {
        $this->get('/cadastro')
            ->assertSee('for="confirmar_senha"', false)
            ->assertSee('id="confirmar_senha"', false);
    }

    public function test_paineis_nao_trazem_mais_dados_de_exemplo_no_html(): void
    {
        $this->get('/paciente')->assertDontSee('Helena Marques')->assertDontSee('987654321-0');
        $this->get('/medico')->assertDontSee('Lucas Silva')->assertDontSee('95%');
    }

    #[DataProvider('paineis')]
    public function test_links_do_menu_dos_paineis_apontam_para_secoes_que_existem(string $url): void
    {
        // Regressão: o menu do médico tinha "Meus Pacientes" (#pacientes) sem seção correspondente
        $html = $this->get($url)->assertOk()->getContent();

        preg_match_all('/<a href="#([\w-]+)"/', $html, $links);
        $this->assertNotEmpty($links[1]);

        foreach ($links[1] as $id) {
            $this->assertStringContainsString("id=\"{$id}\"", $html, "O link #{$id} não tem seção na página {$url}");
        }
    }

    public static function paineis(): array
    {
        return [
            'painel do paciente' => ['/paciente'],
            'painel do médico'   => ['/medico'],
        ];
    }

    #[DataProvider('paineis')]
    public function test_paineis_usam_o_visual_do_index_e_tem_os_botoes_de_acao(string $url): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee('<h1', false)           // saudação em h1, estilizado como o do index
            ->assertSee('class="site-header"', false)
            ->assertSee('btn btn-primary', false)
            ->assertSee('id="btn-sair"', false);
    }

    public function test_botoes_de_acao_dos_paineis_existem(): void
    {
        $this->get('/paciente')->assertSee('id="btn-agendar"', false);

        $this->get('/medico')
            ->assertSee('id="btn-encaixe"', false)
            ->assertSee('id="btn-receita"', false)
            ->assertSee('id="btn-exames"', false)
            ->assertSee('id="btn-atestado"', false)
            ->assertSee('id="area-impressao"', false);
    }

    public function test_pagina_inexistente_responde_404(): void
    {
        $this->get('/nao-existe')->assertNotFound();
    }
}
