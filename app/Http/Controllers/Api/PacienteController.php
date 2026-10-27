<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    /**
     * Busca de pacientes por nome ou CPF (médicos e administradores), usada para marcar encaixes.
     * GET /api/pacientes?busca=maria  (mínimo de 3 caracteres, no máximo 10 resultados)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'busca' => 'required|string|min:3|max:100',
        ]);

        // Escapa % e _ para que sejam buscados como texto comum
        $termo = '%' . addcslashes(trim($request->query('busca')), '%_\\') . '%';

        $pacientes = Paciente::query()
            ->where(fn ($q) => $q->where('nome', 'like', $termo)->orWhere('cpf', 'like', $termo))
            ->orderBy('nome')
            ->limit(10)
            ->get(['id', 'nome', 'cpf', 'data_nascimento']);

        return response()->json($pacientes);
    }
}
