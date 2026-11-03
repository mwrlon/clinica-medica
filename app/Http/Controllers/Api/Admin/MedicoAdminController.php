<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MedicoAdminController extends Controller
{
    /**
     * Listagem completa de médicos para o Painel Admin
     */
    public function index()
    {
        $medicos = Medico::with('especialidade')
            ->withCount('consultas')
            ->orderBy('nome')
            ->get();

        return response()->json($medicos);
    }

    /**
     * Listagem pública (apenas médicos ativos)
     */
    public function indexPublico()
    {
        $medicos = Medico::with('especialidade')
            ->where('status', 'ativo')
            ->get(['id', 'nome', 'crm', 'especialidade_id']);
            
        return response()->json($medicos);
    }

    /**
     * Cadastrar Novo Médico (Ação do Admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'             => 'required|string|max:150',
            'crm'              => 'required|string|max:20|unique:medicos,crm',
            'especialidade_id' => 'required|exists:especialidades,id',
            'telefone'         => 'required|string|max:20',
            'email'            => 'required|email|max:150|unique:medicos,email',
            'senha'            => 'required|string|min:8',
        ]);

        $medico = Medico::create([
            'nome'             => $validated['nome'],
            'crm'              => $validated['crm'],
            'especialidade_id' => $validated['especialidade_id'],
            'telefone'         => $validated['telefone'],
            'email'            => $validated['email'],
            'senha_hash'       => Hash::make($validated['senha']),
            'status'           => 'ativo',
        ]);

        return response()->json([
            'mensagem' => 'Médico cadastrado com sucesso!',
            'medico'   => $medico
        ], 201);
    }

    /**
     * Detalhes de um médico específico
     */
    public function show($id)
    {
        $medico = Medico::with('especialidade')->findOrFail($id);
        return response()->json($medico);
    }

    /**
     * Atualizar dados do Médico (Inativar, alterar telefone, especialidade, etc.)
     */
    public function update(Request $request, $id)
    {
        $medico = Medico::findOrFail($id);

        $validated = $request->validate([
            'nome'             => 'sometimes|required|string|max:150',
            'crm'              => 'sometimes|required|string|max:20|unique:medicos,crm,' . $id,
            'especialidade_id' => 'sometimes|required|exists:especialidades,id',
            'telefone'         => 'sometimes|required|string|max:20',
            'email'            => 'sometimes|required|email|max:150|unique:medicos,email,' . $id,
            'status'           => 'sometimes|required|in:ativo,inativo',
            'senha'            => 'nullable|string|min:8',
        ]);

        if (isset($validated['senha'])) {
            $validated['senha_hash'] = Hash::make($validated['senha']);
            unset($validated['senha']);
        }

        $medico->update($validated);

        return response()->json([
            'mensagem' => 'Dados do médico atualizados com sucesso!',
            'medico'   => $medico
        ]);
    }

    /**
     * Remover médico
     */
    public function destroy($id)
    {
        $medico = Medico::findOrFail($id);

        // A chave estrangeira das consultas é "cascadeOnDelete": excluir um médico com histórico
        // apagaria também as consultas e os atendimentos dele. Nesse caso o cadastro deve ser inativado.
        if ($medico->consultas()->exists()) {
            return response()->json([
                'mensagem' => 'Não é possível excluir: este médico possui consultas registradas. Inative o cadastro em vez de excluir.',
            ], 409);
        }

        $medico->delete();

        return response()->json(['mensagem' => 'Médico removido da base de dados.']);
    }
}