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
            'login do administrador' => ['/login-admin'],
            'painel do administrador' => ['/admin'],
            'termos de uso'     => ['/termos'],
            'política de privacidade' => ['/privacidade'],
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
            'painel do administrador' => ['/admin'],
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

    public function test_pagina_inexistente_mostra_a_404_personalizada(): void
    {
        $this->get('/nao-existe')
            ->assertNotFound()
            ->assertSee('Não encontramos esta página')
            ->assertSee('Voltar ao início')
            ->assertSee('href="' . url('/') . '"', false)
            ->assertSee('<title>Página não encontrada - ProSaúde</title>', false)
            ->assertDontSee('Not Found');
    }

    public function test_rota_inexistente_da_api_continua_respondendo_json(): void
    {
        $this->get('/api/nao-existe')
            ->assertNotFound()
            ->assertHeader('Content-Type', 'application/json')
            ->assertDontSee('Não encontramos esta página');
    }

    public function test_cadastro_liga_aos_termos_e_a_politica_de_privacidade(): void
    {
        $this->get('/cadastro')
            ->assertSee('href="' . url('/termos') . '"', false)
            ->assertSee('href="' . url('/privacidade') . '"', false);

        $this->get('/termos')->assertSee('Termos de uso')->assertSee('Cadastro e conta');
        $this->get('/privacidade')->assertSee('Política de privacidade')->assertSee('LGPD');
    }

    public function test_telas_de_login_do_paciente_e_do_medico_levam_ao_acesso_administrativo(): void
    {
        foreach (['/login', '/login-medico'] as $url) {
            $this->get($url)->assertSee('href="' . url('/login-admin') . '"', false);
        }
    }

    public function test_paineis_do_paciente_e_do_medico_tem_a_aba_meu_perfil(): void
    {
        foreach (['/paciente', '/medico', '/admin'] as $url) {
            $this->get($url)
                ->assertSee('<a href="#perfil">', false)
                ->assertSee('data-campo="perfil"', false);
        }
    }

    public function test_painel_do_administrador_tem_as_abas_e_os_botoes(): void
    {
        $this->get('/admin')
            ->assertSee('id="resumo"', false)
            ->assertSee('id="medicos"', false)
            ->assertSee('id="pacientes"', false)
            ->assertSee('id="consultas"', false)
            ->assertSee('id="cadastros"', false)
            ->assertSee('id="btn-novo-medico"', false)
            ->assertSee('id="btn-sair"', false);
    }

    public function test_nenhum_script_usa_alert_confirm_ou_prompt_do_navegador(): void
    {
        // Regressão: os avisos agora são janelas (resources/js/lib/ui.js), nunca o alert() genérico
        $arquivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('js')));
        $verificados = 0;

        foreach ($arquivos as $arquivo) {
            if ($arquivo->getExtension() !== 'js') {
                continue;
            }

            $verificados++;
            $codigo = preg_replace('#//[^\n]*|/\*.*?\*/#s', '', file_get_contents($arquivo->getPathname()));

            $this->assertDoesNotMatchRegularExpression(
                '/(?<![\w.$])(?:window\.)?(alert|confirm|prompt)\s*\(/',
                $codigo,
                "{$arquivo->getFilename()} usa alert/confirm/prompt do navegador"
            );
        }

        $this->assertGreaterThan(10, $verificados);
    }
}
