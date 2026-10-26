<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Especialidade;
use Illuminate\Http\Request;

class EspecialidadeAdminController extends Controller
{
    public function index()
    {
        return response()->json(Especialidade::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100|unique:especialidades,nome',
        ]);

        $especialidade = Especialidade::create($validated);

        return response()->json($especialidade, 201);
    }

    public function destroy($id)
    {
        $especialidade = Especialidade::findOrFail($id);

        // A chave estrangeira de médicos é "cascadeOnDelete": excluir uma especialidade em uso
        // apagaria todos os médicos dela (e suas consultas). Por isso o bloqueio.
        if ($especialidade->medicos()->exists()) {
            return response()->json([
                'mensagem' => 'Não é possível excluir: há médicos vinculados a esta especialidade.',
            ], 409);
        }

        $especialidade->delete();

        return response()->json(['message' => 'Especialidade excluída com sucesso.']);
    }
}
