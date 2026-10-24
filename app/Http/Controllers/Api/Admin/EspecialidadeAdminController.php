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
            'nome' => 'required|string|unique:especialidades,nome',
        ]);

        $especialidade = Especialidade::create($validated);

        return response()->json($especialidade, 201);
    }

    public function destroy($id)
    {
        $especialidade = Especialidade::findOrFail($id);
        $especialidade->delete();

        return response()->json(['message' => 'Especialidade excluída com sucesso.']);
    }
}