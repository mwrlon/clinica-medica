<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CriaDados;
use Tests\TestCase;

class BuscaPacientesTest extends TestCase
{
    use RefreshDatabase, CriaDados;

    public function test_medico_busca_por_nome_ou_cpf_e_so_recebe_os_campos_necessarios(): void
    {
        $this->paciente(['nome' => 'Mariana Oliveira', 'cpf' => '111.222.333-44']);
        $this->paciente(['nome' => 'Marcos Vinícius', 'cpf' => '555.666.777-88']);
        $this->paciente(['nome' => 'Helena Marques', 'cpf' => '999.000.111-22']);
        Sanctum::actingAs($this->medico());

        $this->getJson('/api/pacientes?busca=mar')->assertOk()->assertJsonCount(3)
            ->assertJsonPath('0.nome', 'Helena Marques')   // ordenado por nome
            ->assertJsonStructure([['id', 'nome', 'cpf', 'data_nascimento']])
            ->assertJsonMissingPath('0.email')->assertJsonMissingPath('0.senha_hash');

        $this->getJson('/api/pacientes?busca=Mariana')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/pacientes?busca=555.666')->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Marcos Vinícius');
    }

    public function test_administrador_tambem_pode_buscar(): void
    {
        $this->paciente(['nome' => 'Helena Marques']);
        Sanctum::actingAs($this->administrador());

        $this->getJson('/api/pacientes?busca=Helena')->assertOk()->assertJsonCount(1);
    }

    public function test_paciente_e_visitante_nao_podem_buscar(): void
    {
        $this->getJson('/api/pacientes?busca=maria')->assertStatus(401);

        Sanctum::actingAs($this->paciente());
        $this->getJson('/api/pacientes?busca=maria')->assertStatus(403);
    }

    public function test_busca_exige_ao_menos_3_caracteres(): void
    {
        Sanctum::actingAs($this->medico());

        $this->getJson('/api/pacientes')->assertStatus(422)->assertJsonValidationErrors(['busca']);
        $this->getJson('/api/pacientes?busca=ma')->assertStatus(422)->assertJsonValidationErrors(['busca']);
    }

    public function test_curingas_do_like_sao_tratados_como_texto(): void
    {
        $this->paciente(['nome' => 'Helena Marques']);
        Sanctum::actingAs($this->medico());

        $this->getJson('/api/pacientes?busca=' . urlencode('%%%'))->assertOk()->assertJsonCount(0);
        $this->getJson('/api/pacientes?busca=' . urlencode('H_l'))->assertOk()->assertJsonCount(0);
    }

    public function test_devolve_no_maximo_10_resultados(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->paciente(['nome' => "Silva Teste {$i}"]);
        }
        Sanctum::actingAs($this->medico());

        $this->getJson('/api/pacientes?busca=Silva')->assertOk()->assertJsonCount(10);
    }
}
