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

    public function test_pagina_inexistente_responde_404(): void
    {
        $this->get('/nao-existe')->assertNotFound();
    }
}
