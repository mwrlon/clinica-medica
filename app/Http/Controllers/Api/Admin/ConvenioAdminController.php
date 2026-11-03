<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Convenio;
use Illuminate\Http\Request;

class ConvenioAdminController extends Controller
{
    /** Painel admin: convênios com a quantidade de pacientes de cada um. */
    public function index()
    {
        return response()->json(Convenio::withCount('pacientes')->orderBy('nome')->get());
    }

    /** Público: lista para os formulários (cadastro e "Meu perfil" do paciente). */
    public function indexPublico()
    {
        return response()->json(Convenio::orderBy('nome')->get(['id', 'nome']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:100|unique:convenios,nome',
        ]);

        return response()->json(Convenio::create($validated), 201);
    }

    public function destroy($id)
    {
        $convenio = Convenio::findOrFail($id);

        // A chave estrangeira dos pacientes é "nullOnDelete": excluir um convênio em uso
        // transformaria todos os pacientes dele em "particular" sem aviso.
        if ($convenio->pacientes()->exists()) {
            return response()->json([
                'mensagem' => 'Não é possível excluir: há pacientes vinculados a este convênio.',
            ], 409);
        }

        $convenio->delete();

        return response()->json(['mensagem' => 'Convênio excluído com sucesso.']);
    }
}
