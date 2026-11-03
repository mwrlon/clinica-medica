<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sala;
use Illuminate\Http\Request;

class SalaAdminController extends Controller
{
    /** Salas com a quantidade de consultas de cada uma. */
    public function index()
    {
        return response()->json(Sala::withCount('consultas')->orderBy('identificacao')->get());
    }

    public function store(Request $request)
    {
        // A tabela "salas" possui apenas a coluna "identificacao" (ex.: "Consultório 01")
        $validated = $request->validate([
            'identificacao' => 'required|string|max:30|unique:salas,identificacao',
        ]);

        return response()->json(Sala::create($validated), 201);
    }

    public function destroy($id)
    {
        $sala = Sala::findOrFail($id);

        // A chave estrangeira das consultas é "cascadeOnDelete": excluir uma sala usada
        // apagaria o histórico de consultas que passaram por ela.
        if ($sala->consultas()->exists()) {
            return response()->json([
                'mensagem' => 'Não é possível excluir: há consultas registradas nesta sala.',
            ], 409);
        }

        $sala->delete();

        return response()->json(['mensagem' => 'Sala excluída com sucesso.']);
    }
}
